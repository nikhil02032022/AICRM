<?php

declare(strict_types=1);

namespace App\Http\Controllers\CRM\Api;

use App\Http\Requests\CRM\OfferLetter\GenerateOfferLetterRequest;
use App\Http\Requests\CRM\OfferLetter\RecordOfferAcceptanceRequest;
use App\Http\Resources\CRM\OfferLetterResource;
use App\Models\CRM\Application;
use App\Models\CRM\OfferLetter;
use App\Services\CRM\Application\OfferLetterService;
use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;

// BRD: CRM-AP-012, CRM-AP-013, CRM-AP-015 — Offer letter API operations
final class OfferLetterController
{
    public function __construct(
        private readonly OfferLetterService $offerLetterService,
    ) {}

    /**
     * List offer letters for an application (API endpoint).
     * GET /api/v1/crm/applications/{application:uuid}/offers
     */
    public function index(Application $application): JsonResponse
    {
        $offerLetters = $application->offerLetters()
            ->orderByDesc('created_at')
            ->paginate(15);

        return response()->json([
            'success' => true,
            'data' => OfferLetterResource::collection($offerLetters),
            'meta' => [
                'total' => $offerLetters->total(),
                'per_page' => $offerLetters->perPage(),
                'current_page' => $offerLetters->currentPage(),
            ],
        ]);
    }

    /**
     * Get a specific offer letter (API endpoint).
     * GET /api/v1/crm/offers/{offer:uuid}
     */
    public function show(OfferLetter $offer): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => new OfferLetterResource($offer),
        ]);
    }

    /**
     * Generate a new offer letter for an application (API endpoint).
     * POST /api/v1/crm/applications/{application:uuid}/offers
     *
     * BRD: CRM-AP-012
     */
    public function store(
        Application $application,
        GenerateOfferLetterRequest $request,
    ): JsonResponse {
        try {
            $offerLetter = $this->offerLetterService->issue(
                application: $application,
                programmeUuid: $application->programme_uuid,
                expiresAt: $request->getExpiryDate(),
                reason: $request->input('reason'),
                extraFields: [
                    'conditional' => $request->isConditional(),
                    'required_documents' => $request->getRequiredDocuments(),
                ]
            );

            return response()->json([
                'success' => true,
                'message' => 'Offer letter generated successfully',
                'data' => new OfferLetterResource($offerLetter),
            ], 201);

        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'INVALID_REQUEST',
                    'message' => $e->getMessage(),
                ],
            ], 400);
        }
    }

    /**
     * Record acceptance of an offer letter (API endpoint).
     * POST /api/v1/crm/offers/{offer:uuid}/accept
     *
     * BRD: CRM-AP-015
     */
    public function accept(
        OfferLetter $offer,
        RecordOfferAcceptanceRequest $request,
    ): JsonResponse {
        try {
            // Authorization: applicant or admissions staff can accept
            if (auth()->user()->cannot('update', $offer)) {
                return response()->json([
                    'success' => false,
                    'error' => [
                        'code' => 'UNAUTHORIZED',
                        'message' => 'You are not authorized to accept this offer',
                    ],
                ], 403);
            }

            $this->offerLetterService->recordAcceptance(
                offerLetter: $offer,
                ipAddress: $request->ip(),
                notes: $request->input('notes'),
            );

            return response()->json([
                'success' => true,
                'message' => 'Offer accepted successfully',
                'data' => new OfferLetterResource($offer->refresh()),
            ]);

        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'INVALID_REQUEST',
                    'message' => $e->getMessage(),
                ],
            ], 400);
        }
    }

    /**
     * Record decline of an offer letter (API endpoint).
     * POST /api/v1/crm/offers/{offer:uuid}/decline
     */
    public function decline(
        OfferLetter $offer,
        RecordOfferAcceptanceRequest $request,
    ): JsonResponse {
        try {
            if (auth()->user()->cannot('update', $offer)) {
                return response()->json([
                    'success' => false,
                    'error' => [
                        'code' => 'UNAUTHORIZED',
                        'message' => 'You are not authorized to decline this offer',
                    ],
                ], 403);
            }

            $this->offerLetterService->recordDecline(
                offerLetter: $offer,
                reason: $request->input('reason', 'Not specified'),
                ipAddress: $request->ip(),
            );

            return response()->json([
                'success' => true,
                'message' => 'Offer declined successfully',
                'data' => new OfferLetterResource($offer->refresh()),
            ]);

        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'INVALID_REQUEST',
                    'message' => $e->getMessage(),
                ],
            ], 400);
        }
    }

    /**
     * Send offer letter via channel (email/SMS/WhatsApp).
     * POST /api/v1/crm/offers/{offer:uuid}/send
     *
     * BRD: CRM-AP-013
     */
    public function send(
        OfferLetter $offer,
        \Illuminate\Http\Request $request,
    ): JsonResponse {
        try {
            if (auth()->user()->cannot('send', $offer)) {
                return response()->json([
                    'success' => false,
                    'error' => [
                        'code' => 'UNAUTHORIZED',
                        'message' => 'You are not authorized to send this offer',
                    ],
                ], 403);
            }

            $channel = $request->input('channel', 'email');
            $this->offerLetterService->send(
                offerLetter: $offer,
                channel: $channel,
            );

            return response()->json([
                'success' => true,
                'message' => "Offer sent via {$channel}",
                'data' => new OfferLetterResource($offer->refresh()),
            ]);

        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'INVALID_REQUEST',
                    'message' => $e->getMessage(),
                ],
            ], 400);
        }
    }

    /**
     * Mark a required document as verified on a conditional offer.
     * PATCH /api/v1/crm/offers/{offer:uuid}/documents/{docType}/verify
     *
     * BRD: CRM-AP-014
     */
    public function verifyDocument(
        OfferLetter $offer,
        string $docType,
        \Illuminate\Http\Request $request,
    ): JsonResponse {
        try {
            if (auth()->user()->cannot('update', $offer)) {
                return response()->json([
                    'success' => false,
                    'error' => ['code' => 'UNAUTHORIZED', 'message' => 'Not authorized.'],
                ], 403);
            }

            $verified = (bool) $request->input('verified', true);
            $this->offerLetterService->verifyDocument($offer, $docType, $verified);

            return response()->json([
                'success' => true,
                'message' => "Document '{$docType}' marked as " . ($verified ? 'verified' : 'unverified'),
                'data' => new OfferLetterResource($offer->refresh()),
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'error' => ['code' => 'INVALID_REQUEST', 'message' => $e->getMessage()],
            ], 422);
        }
    }

    /**
     * Download offer letter PDF (signed URL).
     * GET /api/v1/crm/offers/{offer:uuid}/download
     */
    public function download(OfferLetter $offer): JsonResponse
    {
        if (! $offer->pdf_path) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'PDF not yet generated',
                ],
            ], 404);
        }

        // Generate signed URL for secure download
        $downloadUrl = $this->generateSignedDownloadUrl($offer);

        return response()->json([
            'success' => true,
            'data' => [
                'download_url' => $downloadUrl,
                'expires_in_minutes' => 15,
            ],
        ]);
    }

    /**
     * Generate a public portal link for the student to accept/decline.
     * POST /api/v1/crm/offers/{offer:uuid}/portal-link
     *
     * BRD: CRM-AP-015
     */
    public function generatePortalLink(
        OfferLetter $offer,
        \Illuminate\Http\Request $request,
    ): JsonResponse {
        try {
            if (auth()->user()->cannot('update', $offer)) {
                return response()->json([
                    'success' => false,
                    'error' => ['code' => 'UNAUTHORIZED', 'message' => 'Not authorized.'],
                ], 403);
            }

            $expiryHours = (int) $request->input('expiry_hours', 72);
            $token = $this->offerLetterService->generateAcceptanceToken($offer, $expiryHours);
            $url = route('portal.offers.show', $token);

            return response()->json([
                'success' => true,
                'data' => [
                    'portal_url' => $url,
                    'token' => $token,
                    'expires_in_hours' => $expiryHours,
                ],
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'error' => ['code' => 'INVALID_REQUEST', 'message' => $e->getMessage()],
            ], 422);
        }
    }

    private function generateSignedDownloadUrl(OfferLetter $offer): string
    {
        // Generate a temporary signed URL valid for 15 minutes
        return \Illuminate\Support\Facades\Storage::disk('s3')->temporaryUrl(
            $offer->pdf_path,
            now()->addMinutes(15),
        );
    }
}
