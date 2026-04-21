{{-- BRD: CRM-FM-009 — Installment plans --}}
<x-layouts.crm title="Installment Plans">
    <div class="space-y-4" x-data="installmentPlans()">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold text-gray-900">Fee Installment Plans</h2>
                <p class="mt-1 text-sm text-gray-600">Configure installment templates that can be applied to applications.</p>
            </div>
            @can('installment.plan.manage')
                <button type="button" @click="openCreate()" class="btn-primary">
                    + Configure Installment Plan
                </button>
            @endcan
        </div>

        @if (session('status'))
            <div class="rounded-md border border-green-200 bg-green-50 p-3 text-sm text-green-800">{{ session('status') }}</div>
        @endif

        @if ($errors->any())
            <div class="rounded-md border border-red-200 bg-red-50 p-3 text-sm text-red-800">
                <ul class="list-disc list-inside space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="overflow-hidden rounded-lg border border-gray-200 bg-white">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="border-b border-gray-200 bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700">Name</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700">Programme</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700">Fee Type</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold text-gray-700">Total</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700">Rows</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700">Status</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold text-gray-700">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse ($plans as $plan)
                            <tr class="transition-colors hover:bg-gray-50">
                                <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ $plan->name }}</td>
                                <td class="px-6 py-4 text-sm text-gray-600">{{ $plan->programme?->name ?? 'All' }}</td>
                                <td class="px-6 py-4 text-sm">
                                    <span class="inline-flex rounded-full bg-indigo-50 px-2.5 py-0.5 text-xs font-medium text-indigo-700">{{ $plan->fee_type?->label() }}</span>
                                </td>
                                <td class="px-6 py-4 text-right text-sm font-semibold text-gray-900">{{ number_format((float) $plan->total_amount, 2) }}</td>
                                <td class="px-6 py-4 text-sm text-gray-600">{{ count($plan->schedule ?? []) }}</td>
                                <td class="px-6 py-4">
                                    @if ($plan->is_active)
                                        <span class="inline-flex rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-semibold text-green-800">Active</span>
                                    @else
                                        <span class="inline-flex rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-600">Inactive</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-right space-x-2">
                                    @can('installment.plan.manage')
                                        <button type="button"
                                            @click="openEdit({{ json_encode(['id' => $plan->id, 'name' => $plan->name, 'programme_id' => $plan->programme_id, 'fee_type' => $plan->fee_type?->value, 'total_amount' => $plan->total_amount, 'schedule' => $plan->schedule ?? [], 'is_active' => $plan->is_active]) }})"
                                            class="btn-secondary-sm">Edit</button>
                                        <form method="POST" action="{{ route('crm.payments.installments.toggle', $plan) }}" class="inline">
                                            @csrf
                                            <button type="submit" class="btn-secondary-sm">Toggle</button>
                                        </form>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="px-4 py-16 text-center text-sm text-gray-500">No installment plans configured yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($plans->hasPages())
                <div class="border-t border-gray-200 px-6 py-3">{{ $plans->links() }}</div>
            @endif
        </div>

        {{-- Create / Edit Modal --}}
        <div x-show="showModal" class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto bg-black/40 py-8" x-cloak>
            <div class="w-full max-w-2xl rounded-xl bg-white shadow-xl mx-4" @click.stop>
                <div class="flex items-center justify-between border-b border-gray-200 px-6 py-4">
                    <h3 class="text-lg font-semibold text-gray-900" x-text="editId ? 'Edit Installment Plan' : 'Configure Installment Plan'"></h3>
                    <button type="button" @click="showModal = false" class="text-gray-400 hover:text-gray-600">&times;</button>
                </div>

                <form :action="editId ? '{{ url('crm/payments/installments') }}/' + editId : '{{ route('crm.payments.installments.store') }}'"
                      method="POST" class="px-6 py-4 space-y-4">
                    @csrf
                    <template x-if="editId"><input type="hidden" name="_method" value="PUT"></template>

                    {{-- Name --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Plan Name <span class="text-red-500">*</span></label>
                        <input type="text" name="name" x-model="form.name" required maxlength="120"
                               class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
                               placeholder="e.g. 3-Instalment Tuition Plan">
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        {{-- Programme --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Programme</label>
                            <select name="programme_id" x-model="form.programme_id"
                                    class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                                <option value="">All Programmes</option>
                                @foreach ($programmes as $programme)
                                    <option value="{{ $programme->id }}">{{ $programme->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Fee Type --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Fee Type <span class="text-red-500">*</span></label>
                            <select name="fee_type" x-model="form.fee_type" required
                                    class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                                <option value="">Select fee type</option>
                                <option value="application">Application Fee</option>
                                <option value="seat_booking">Seat Booking Fee</option>
                                <option value="tuition_advance">Tuition Advance</option>
                            </select>
                        </div>
                    </div>

                    {{-- Total Amount --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Total Amount <span class="text-red-500">*</span></label>
                        <input type="number" name="total_amount" x-model="form.total_amount" required min="0" step="0.01"
                               class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
                               placeholder="0.00">
                    </div>

                    {{-- Schedule Rows --}}
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <label class="block text-sm font-medium text-gray-700">Instalment Schedule <span class="text-red-500">*</span></label>
                            <button type="button" @click="addRow()" class="text-xs text-indigo-600 hover:text-indigo-800 font-medium">+ Add Row</button>
                        </div>
                        <div class="overflow-x-auto rounded-md border border-gray-200">
                            <table class="w-full text-xs">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-3 py-2 text-left font-semibold text-gray-600">#</th>
                                        <th class="px-3 py-2 text-left font-semibold text-gray-600">Label</th>
                                        <th class="px-3 py-2 text-right font-semibold text-gray-600">% of Total</th>
                                        <th class="px-3 py-2 text-right font-semibold text-gray-600">Due (days)</th>
                                        <th class="px-3 py-2"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template x-for="(row, index) in form.schedule" :key="index">
                                        <tr class="border-t border-gray-100">
                                            <td class="px-3 py-2">
                                                <input type="hidden" :name="'schedule[' + index + '][sequence]'" :value="index + 1">
                                                <span x-text="index + 1" class="text-gray-500"></span>
                                            </td>
                                            <td class="px-3 py-2">
                                                <input type="text" :name="'schedule[' + index + '][label]'" x-model="row.label" maxlength="120"
                                                       class="w-full rounded border border-gray-300 px-2 py-1 text-xs focus:border-indigo-400 focus:outline-none"
                                                       placeholder="e.g. First Instalment">
                                            </td>
                                            <td class="px-3 py-2">
                                                <input type="number" :name="'schedule[' + index + '][percent]'" x-model="row.percent"
                                                       required min="0" max="100" step="0.01"
                                                       class="w-20 rounded border border-gray-300 px-2 py-1 text-xs text-right focus:border-indigo-400 focus:outline-none"
                                                       placeholder="0">
                                            </td>
                                            <td class="px-3 py-2">
                                                <input type="number" :name="'schedule[' + index + '][due_offset_days]'" x-model="row.due_offset_days"
                                                       required min="0"
                                                       class="w-20 rounded border border-gray-300 px-2 py-1 text-xs text-right focus:border-indigo-400 focus:outline-none"
                                                       placeholder="0">
                                            </td>
                                            <td class="px-3 py-2 text-center">
                                                <button type="button" @click="removeRow(index)" class="text-red-400 hover:text-red-600 font-bold">&times;</button>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                                <tfoot class="border-t border-gray-200 bg-gray-50">
                                    <tr>
                                        <td colspan="2" class="px-3 py-1 text-xs text-gray-500">Total must sum to 100%</td>
                                        <td class="px-3 py-1 text-right text-xs font-semibold" :class="percentSum == 100 ? 'text-green-600' : 'text-red-500'" x-text="percentSum.toFixed(2) + '%'"></td>
                                        <td colspan="2"></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>

                    {{-- Active --}}
                    <div class="flex items-center gap-2">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" id="is_active" value="1" x-model="form.is_active"
                               class="h-4 w-4 rounded border-gray-300 text-indigo-600">
                        <label for="is_active" class="text-sm text-gray-700">Active (available for assignment)</label>
                    </div>

                    <div class="flex justify-end gap-3 border-t border-gray-100 pt-4">
                        <button type="button" @click="showModal = false" class="btn-secondary">Cancel</button>
                        <button type="submit" class="btn-primary" :disabled="percentSum != 100 || form.schedule.length === 0">
                            <span x-text="editId ? 'Save Changes' : 'Create Plan'"></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        function installmentPlans() {
            return {
                showModal: false,
                editId: null,
                form: {
                    name: '',
                    programme_id: '',
                    fee_type: '',
                    total_amount: '',
                    schedule: [],
                    is_active: true,
                },
                get percentSum() {
                    return this.form.schedule.reduce((sum, r) => sum + (parseFloat(r.percent) || 0), 0);
                },
                openCreate() {
                    this.editId = null;
                    this.form = { name: '', programme_id: '', fee_type: '', total_amount: '', schedule: [], is_active: true };
                    this.addRow();
                    this.showModal = true;
                },
                openEdit(plan) {
                    this.editId = plan.id;
                    this.form = {
                        name: plan.name,
                        programme_id: plan.programme_id ?? '',
                        fee_type: plan.fee_type ?? '',
                        total_amount: plan.total_amount,
                        schedule: (plan.schedule || []).map(r => ({
                            label: r.label ?? '',
                            percent: r.percent,
                            due_offset_days: r.due_offset_days,
                        })),
                        is_active: plan.is_active,
                    };
                    this.showModal = true;
                },
                addRow() {
                    this.form.schedule.push({ label: '', percent: '', due_offset_days: 0 });
                },
                removeRow(index) {
                    this.form.schedule.splice(index, 1);
                },
            };
        }
    </script>
    @endpush
</x-layouts.crm>
