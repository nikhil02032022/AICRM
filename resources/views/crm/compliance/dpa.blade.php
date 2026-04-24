<x-layouts.crm title="Data Processing Agreement">
    <div class="space-y-6">

        {{-- Page Header --}}
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Data Processing Agreement</h1>
                <p class="mt-1 text-sm text-gray-500">CR-009 — DPDP Act 2023 compliance documentation</p>
            </div>
            <a href="{{ route('crm.compliance.dpa.show') }}?download=1" class="btn-primary">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                </svg>
                Download PDF
            </a>
        </div>

        {{-- DPA Document --}}
        <div class="card p-8 max-w-4xl mx-auto space-y-8 text-sm leading-relaxed text-gray-700">

            {{-- Document Header --}}
            <div class="text-center border-b border-gray-200 pb-6">
                <h2 class="text-xl font-bold text-gray-900 mb-1">DATA PROCESSING AGREEMENT</h2>
                <p class="text-xs text-gray-500">In accordance with the Digital Personal Data Protection Act, 2023 (India)</p>
                <p class="text-xs text-gray-400 mt-1">Reference: CR-009 &nbsp;·&nbsp; Version 1.0</p>
            </div>

            {{-- 1. Parties --}}
            <section>
                <h3 class="text-base font-bold text-gray-900 mb-3 flex items-center gap-2">
                    <span class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-indigo-100 text-xs font-bold text-indigo-700">1</span>
                    Parties
                </h3>
                <div class="pl-8 space-y-2">
                    <p>This Data Processing Agreement ("Agreement") is entered into between:</p>
                    <ul class="list-disc list-inside space-y-1 text-gray-700 pl-2">
                        <li><strong>Data Fiduciary:</strong> The Institution operating this CRM platform (hereinafter "Institution")</li>
                        <li><strong>Data Principal:</strong> Any individual whose personal data is collected and processed through this platform (hereinafter "Lead" or "Applicant")</li>
                    </ul>
                </div>
            </section>

            {{-- 2. Scope --}}
            <section>
                <h3 class="text-base font-bold text-gray-900 mb-3 flex items-center gap-2">
                    <span class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-indigo-100 text-xs font-bold text-indigo-700">2</span>
                    Scope
                </h3>
                <div class="pl-8">
                    <p>This Agreement governs the collection, storage, processing, and deletion of personal data through the CRM system, including all modules: Lead Management, Application Processing, Communication, and Alumni Management.</p>
                </div>
            </section>

            {{-- 3. Purpose --}}
            <section>
                <h3 class="text-base font-bold text-gray-900 mb-3 flex items-center gap-2">
                    <span class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-indigo-100 text-xs font-bold text-indigo-700">3</span>
                    Purpose of Processing
                </h3>
                <div class="pl-8">
                    <p>Personal data is processed for the following lawful purposes:</p>
                    <ul class="list-disc list-inside mt-2 space-y-1 pl-2">
                        <li>Admission enquiries and application processing</li>
                        <li>Academic counselling and guidance</li>
                        <li>Marketing communications (with explicit consent)</li>
                        <li>Compliance, audit, and regulatory reporting</li>
                        <li>Alumni relationship management</li>
                    </ul>
                </div>
            </section>

            {{-- 4. Data Categories --}}
            <section>
                <h3 class="text-base font-bold text-gray-900 mb-3 flex items-center gap-2">
                    <span class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-indigo-100 text-xs font-bold text-indigo-700">4</span>
                    Data Categories
                </h3>
                <div class="pl-8">
                    <p>The following categories of personal data are processed:</p>
                    <div class="mt-2 overflow-hidden rounded-lg border border-gray-200">
                        <table class="min-w-full text-xs">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-2 text-left font-semibold text-gray-700">Category</th>
                                    <th class="px-4 py-2 text-left font-semibold text-gray-700">Examples</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr><td class="px-4 py-2 font-medium">Identity Data</td><td class="px-4 py-2 text-gray-600">Name, Date of Birth, Gender, Aadhaar reference</td></tr>
                                <tr><td class="px-4 py-2 font-medium">Contact Data</td><td class="px-4 py-2 text-gray-600">Email, Phone, WhatsApp number, Address</td></tr>
                                <tr><td class="px-4 py-2 font-medium">Academic Data</td><td class="px-4 py-2 text-gray-600">Qualifications, Programme interest, Application details</td></tr>
                                <tr><td class="px-4 py-2 font-medium">Technical Data</td><td class="px-4 py-2 text-gray-600">IP address, Browser, UTM parameters, Session data</td></tr>
                                <tr><td class="px-4 py-2 font-medium">Financial Data</td><td class="px-4 py-2 text-gray-600">Payment records, Scholarship information</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>

            {{-- 5. Retention --}}
            <section>
                <h3 class="text-base font-bold text-gray-900 mb-3 flex items-center gap-2">
                    <span class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-indigo-100 text-xs font-bold text-indigo-700">5</span>
                    Data Retention
                </h3>
                <div class="pl-8 space-y-2">
                    <p>Data is retained in accordance with institutional policy and legal requirements:</p>
                    <ul class="list-disc list-inside mt-2 space-y-1 pl-2">
                        <li>Active leads: Retained for the duration of the enquiry cycle plus 2 years</li>
                        <li>Enrolled students / Alumni: Retained for 7 years post-graduation</li>
                        <li>Opted-out leads: PII anonymised within 30 days of erasure request</li>
                        <li>Audit and consent logs: Retained for 7 years</li>
                    </ul>
                </div>
            </section>

            {{-- 6. Security Measures --}}
            <section>
                <h3 class="text-base font-bold text-gray-900 mb-3 flex items-center gap-2">
                    <span class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-indigo-100 text-xs font-bold text-indigo-700">6</span>
                    Security Measures
                </h3>
                <div class="pl-8">
                    <ul class="list-disc list-inside space-y-1 pl-2">
                        <li>TLS 1.2+ encryption for all data in transit</li>
                        <li>AES-256 encryption for sensitive fields at rest</li>
                        <li>Role-based access control (RBAC) with least-privilege principles</li>
                        <li>Multi-factor authentication for CRM staff</li>
                        <li>Audit logging for all data access and modifications</li>
                        <li>Regular penetration testing and vulnerability assessments</li>
                    </ul>
                </div>
            </section>

            {{-- 7. Sub-Processors --}}
            <section>
                <h3 class="text-base font-bold text-gray-900 mb-3 flex items-center gap-2">
                    <span class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-indigo-100 text-xs font-bold text-indigo-700">7</span>
                    Sub-Processors
                </h3>
                <div class="pl-8">
                    <p>The following categories of sub-processors may be engaged:</p>
                    <ul class="list-disc list-inside mt-2 space-y-1 pl-2">
                        <li>Cloud infrastructure provider (hosting and storage)</li>
                        <li>Email delivery service (transactional and marketing emails)</li>
                        <li>SMS / WhatsApp gateway provider</li>
                        <li>Payment gateway (for application fees)</li>
                        <li>AI / LLM providers (for lead scoring and recommendations)</li>
                    </ul>
                    <p class="mt-2 text-xs text-gray-500">All sub-processors are bound by data processing agreements consistent with this Agreement.</p>
                </div>
            </section>

            {{-- 8. Breach Notification --}}
            <section>
                <h3 class="text-base font-bold text-gray-900 mb-3 flex items-center gap-2">
                    <span class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-indigo-100 text-xs font-bold text-indigo-700">8</span>
                    Breach Notification
                </h3>
                <div class="pl-8 space-y-2">
                    <p>In the event of a personal data breach:</p>
                    <ul class="list-disc list-inside mt-2 space-y-1 pl-2">
                        <li>The Data Protection Board of India shall be notified within <strong>72 hours</strong> of discovery</li>
                        <li>Affected Data Principals shall be notified without undue delay</li>
                        <li>Notification shall include: nature of breach, categories affected, likely consequences, and remediation measures</li>
                        <li>All breaches shall be logged in the Security Incidents module (CR-010)</li>
                    </ul>
                </div>
            </section>

            {{-- 9. Rights of Data Subjects --}}
            <section>
                <h3 class="text-base font-bold text-gray-900 mb-3 flex items-center gap-2">
                    <span class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-indigo-100 text-xs font-bold text-indigo-700">9</span>
                    Rights of Data Principals
                </h3>
                <div class="pl-8">
                    <p>Under the DPDP Act 2023, Data Principals have the following rights:</p>
                    <ul class="list-disc list-inside mt-2 space-y-1 pl-2">
                        <li><strong>Right to Access</strong> — Request a copy of their personal data (CR-004)</li>
                        <li><strong>Right to Correction</strong> — Request correction of inaccurate data</li>
                        <li><strong>Right to Erasure</strong> — Request deletion of personal data within 30 days (CR-005)</li>
                        <li><strong>Right to Grievance Redressal</strong> — Raise complaints with the Data Protection Officer</li>
                        <li><strong>Right to Nominate</strong> — Nominate a representative in case of incapacity</li>
                    </ul>
                    <p class="mt-2 text-xs text-gray-500">Requests can be submitted via the applicant portal or by contacting the Data Protection Officer.</p>
                </div>
            </section>

            {{-- 10. Governing Law --}}
            <section>
                <h3 class="text-base font-bold text-gray-900 mb-3 flex items-center gap-2">
                    <span class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-indigo-100 text-xs font-bold text-indigo-700">10</span>
                    Governing Law
                </h3>
                <div class="pl-8">
                    <p>This Agreement shall be governed by and construed in accordance with:</p>
                    <ul class="list-disc list-inside mt-2 space-y-1 pl-2">
                        <li>The Digital Personal Data Protection Act, 2023 (India)</li>
                        <li>The Information Technology Act, 2000 and its amendments</li>
                        <li>Any rules, regulations, and guidelines issued by the Data Protection Board of India</li>
                    </ul>
                    <p class="mt-2">Any disputes shall be subject to the exclusive jurisdiction of courts in India.</p>
                </div>
            </section>

            {{-- Footer --}}
            <div class="border-t border-gray-200 pt-4 text-xs text-gray-400 text-center">
                This document is auto-generated by the CRM compliance module. Reference: CR-009 &nbsp;|&nbsp; Generated: {{ now()->format('d M Y') }}
            </div>
        </div>

    </div>
</x-layouts.crm>
