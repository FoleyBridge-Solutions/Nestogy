<?php

namespace App\Domains\Client\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\ClientTrip;
use App\Traits\UsesSelectedClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class TripController extends Controller
{
    use UsesSelectedClient;

    private const NULLABLE_NUMERIC_MIN_ZERO = 'nullable|numeric|min:0';

    /**
     * Display a listing of trips for the selected client
     */
    public function index(Request $request)
    {
        $client = $this->getSelectedClient($request);

        if (! $client) {
            return redirect()->route('clients.select-screen');
        }

        $query = $client->trips()->with(['client', 'creator', 'approver']);

        // Apply search filters
        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('trip_number', 'like', "%{$search}%")
                    ->orWhere('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('destination_city', 'like', "%{$search}%");
            });
        }

        // Apply status filter
        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        // Apply trip type filter
        if ($tripType = $request->get('trip_type')) {
            $query->where('trip_type', $tripType);
        }

        // Apply transportation filter
        if ($transportation = $request->get('transportation_mode')) {
            $query->where('transportation_mode', $transportation);
        }

        // Apply date filters
        if ($request->get('upcoming_only')) {
            $query->upcoming();
        } elseif ($request->get('current_only')) {
            $query->current();
        } elseif ($request->get('completed_only')) {
            $query->completed();
        } elseif ($request->get('pending_approval')) {
            $query->pendingApproval();
        } elseif ($request->get('follow_up_required')) {
            $query->followUpRequired();
        }

        // Apply expense filters
        if ($request->get('billable_only')) {
            $query->billable();
        } elseif ($request->get('reimbursable_only')) {
            $query->reimbursable();
        }

        $trips = $query->orderBy('start_date', 'desc')
            ->paginate(20)
            ->appends($request->query());

        $statuses = ClientTrip::getStatuses();
        $tripTypes = ClientTrip::getTripTypes();
        $transportationModes = ClientTrip::getTransportationModes();

        return view('clients.trips.index', compact('trips', 'client', 'statuses', 'tripTypes', 'transportationModes'));
    }

    /**
     * Show the form for creating a new trip
     */
    public function create(Request $request)
    {
        $clients = Client::where('company_id', auth()->user()->company_id)
            ->orderBy('name')
            ->get();

        $selectedClientId = $request->get('client_id');
        $statuses = ClientTrip::getStatuses();
        $tripTypes = ClientTrip::getTripTypes();
        $transportationModes = ClientTrip::getTransportationModes();
        $currencies = ClientTrip::getCurrencies();

        return view('clients.trips.create', compact('clients', 'selectedClientId', 'statuses', 'tripTypes', 'transportationModes', 'currencies'));
    }

    /**
     * Store a newly created trip
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'client_id' => [
                'required',
                'exists:clients,id',
                Rule::exists('clients', 'id')->where(function ($query) {
                    $query->where('company_id', auth()->user()->company_id);
                }),
            ],
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'purpose' => 'nullable|string|max:255',
            'destination_address' => 'nullable|string|max:255',
            'destination_city' => 'required|string|max:100',
            'destination_state' => 'nullable|string|max:100',
            'destination_country' => 'nullable|string|max:100',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'departure_time' => 'nullable|date',
            'return_time' => 'nullable|date|after:departure_time',
            'status' => 'required|in:'.implode(',', array_keys(ClientTrip::getStatuses())),
            'trip_type' => 'required|in:'.implode(',', array_keys(ClientTrip::getTripTypes())),
            'transportation_mode' => 'required|in:'.implode(',', array_keys(ClientTrip::getTransportationModes())),
            'accommodation_details' => 'nullable|string',
            'estimated_expenses' => self::NULLABLE_NUMERIC_MIN_ZERO,
            'currency' => 'required|in:'.implode(',', array_keys(ClientTrip::getCurrencies())),
            'mileage' => self::NULLABLE_NUMERIC_MIN_ZERO,
            'per_diem_amount' => self::NULLABLE_NUMERIC_MIN_ZERO,
            'billable_to_client' => 'boolean',
            'reimbursable' => 'boolean',
            'approval_required' => 'boolean',
            'attendees' => 'nullable|string',
            'notes' => 'nullable|string',
            'expense_breakdown' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $trip = new ClientTrip([
            'client_id' => $request->client_id,
            'trip_number' => ClientTrip::generateTripNumber(),
            'title' => $request->title,
            'description' => $request->description,
            'purpose' => $request->purpose,
            'destination_address' => $request->destination_address,
            'destination_city' => $request->destination_city,
            'destination_state' => $request->destination_state,
            'destination_country' => $request->destination_country,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'departure_time' => $request->departure_time,
            'return_time' => $request->return_time,
            'status' => $request->status,
            'trip_type' => $request->trip_type,
            'transportation_mode' => $request->transportation_mode,
            'accommodation_details' => $request->accommodation_details,
            'estimated_expenses' => $request->estimated_expenses ?: 0,
            'currency' => $request->currency,
            'mileage' => $request->mileage ?: 0,
            'per_diem_amount' => $request->per_diem_amount ?: 0,
            'billable_to_client' => $request->has('billable_to_client'),
            'reimbursable' => $request->has('reimbursable'),
            'approval_required' => $request->has('approval_required'),
            'attendees' => $this->processAttendees($request->attendees),
            'notes' => $request->notes,
            'expense_breakdown' => $this->processExpenseBreakdown($request->expense_breakdown),
            'created_by' => auth()->id(),
        ]);

        $trip->company_id = auth()->user()->company_id;
        $trip->save();

        return redirect()->route('clients.trips.standalone.index')
            ->with('success', 'Trip created successfully.');
    }

    /**
     * Display the specified trip
     */
    public function show(ClientTrip $trip)
    {
        $this->authorize('view', $trip);

        $trip->load('client', 'creator', 'approver');

        return view('clients.trips.show', compact('trip'));
    }

    /**
     * Show the form for editing the specified trip
     */
    public function edit(ClientTrip $trip)
    {
        $this->authorize('update', $trip);

        $clients = Client::where('company_id', auth()->user()->company_id)
            ->orderBy('name')
            ->get();

        $statuses = ClientTrip::getStatuses();
        $tripTypes = ClientTrip::getTripTypes();
        $transportationModes = ClientTrip::getTransportationModes();
        $currencies = ClientTrip::getCurrencies();

        return view('clients.trips.edit', compact('trip', 'clients', 'statuses', 'tripTypes', 'transportationModes', 'currencies'));
    }

    /**
     * Update the specified trip
     */
    public function update(Request $request, ClientTrip $trip)
    {
        $this->authorize('update', $trip);

        $validator = Validator::make($request->all(), [
            'client_id' => [
                'required',
                'exists:clients,id',
                Rule::exists('clients', 'id')->where(function ($query) {
                    $query->where('company_id', auth()->user()->company_id);
                }),
            ],
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'purpose' => 'nullable|string|max:255',
            'destination_address' => 'nullable|string|max:255',
            'destination_city' => 'required|string|max:100',
            'destination_state' => 'nullable|string|max:100',
            'destination_country' => 'nullable|string|max:100',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'departure_time' => 'nullable|date',
            'return_time' => 'nullable|date',
            'status' => 'required|in:'.implode(',', array_keys(ClientTrip::getStatuses())),
            'trip_type' => 'required|in:'.implode(',', array_keys(ClientTrip::getTripTypes())),
            'transportation_mode' => 'required|in:'.implode(',', array_keys(ClientTrip::getTransportationModes())),
            'accommodation_details' => 'nullable|string',
            'estimated_expenses' => self::NULLABLE_NUMERIC_MIN_ZERO,
            'actual_expenses' => self::NULLABLE_NUMERIC_MIN_ZERO,
            'currency' => 'required|in:'.implode(',', array_keys(ClientTrip::getCurrencies())),
            'mileage' => self::NULLABLE_NUMERIC_MIN_ZERO,
            'per_diem_amount' => self::NULLABLE_NUMERIC_MIN_ZERO,
            'billable_to_client' => 'boolean',
            'reimbursable' => 'boolean',
            'approval_required' => 'boolean',
            'follow_up_required' => 'boolean',
            'attendees' => 'nullable|string',
            'notes' => 'nullable|string',
            'client_feedback' => 'nullable|string',
            'internal_rating' => 'nullable|integer|min:1|max:5',
            'expense_breakdown' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $trip->fill([
            'client_id' => $request->client_id,
            'title' => $request->title,
            'description' => $request->description,
            'purpose' => $request->purpose,
            'destination_address' => $request->destination_address,
            'destination_city' => $request->destination_city,
            'destination_state' => $request->destination_state,
            'destination_country' => $request->destination_country,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'departure_time' => $request->departure_time,
            'return_time' => $request->return_time,
            'status' => $request->status,
            'trip_type' => $request->trip_type,
            'transportation_mode' => $request->transportation_mode,
            'accommodation_details' => $request->accommodation_details,
            'estimated_expenses' => $request->estimated_expenses ?: 0,
            'actual_expenses' => $request->actual_expenses ?: 0,
            'currency' => $request->currency,
            'mileage' => $request->mileage ?: 0,
            'per_diem_amount' => $request->per_diem_amount ?: 0,
            'billable_to_client' => $request->has('billable_to_client'),
            'reimbursable' => $request->has('reimbursable'),
            'approval_required' => $request->has('approval_required'),
            'follow_up_required' => $request->has('follow_up_required'),
            'attendees' => $this->processAttendees($request->attendees),
            'notes' => $request->notes,
            'client_feedback' => $request->client_feedback,
            'internal_rating' => $request->internal_rating,
            'expense_breakdown' => $this->processExpenseBreakdown($request->expense_breakdown),
        ]);

        $trip->save();

        return redirect()->route('clients.trips.standalone.index')
            ->with('success', 'Trip updated successfully.');
    }

    /**
     * Remove the specified trip
     */
    public function destroy(ClientTrip $trip)
    {
        $this->authorize('delete', $trip);

        $trip->delete();

        return redirect()->route('clients.trips.standalone.index')
            ->with('success', 'Trip deleted successfully.');
    }

    /**
     * Approve the trip
     */
    public function approve(ClientTrip $trip)
    {
        $this->authorize('update', $trip);

        if ($trip->approve()) {
            return redirect()->route('clients.trips.standalone.show', $trip)
                ->with('success', 'Trip approved successfully.');
        } else {
            return redirect()->back()
                ->with('error', 'Failed to approve trip.');
        }
    }

    /**
     * Start the trip
     */
    public function start(ClientTrip $trip)
    {
        $this->authorize('update', $trip);

        if ($trip->start()) {
            return redirect()->route('clients.trips.standalone.show', $trip)
                ->with('success', 'Trip started successfully.');
        } else {
            return redirect()->back()
                ->with('error', 'Trip cannot be started in its current status.');
        }
    }

    /**
     * Complete the trip
     */
    public function complete(Request $request, ClientTrip $trip)
    {
        $this->authorize('update', $trip);

        $validator = Validator::make($request->all(), [
            'actual_expenses' => self::NULLABLE_NUMERIC_MIN_ZERO,
            'client_feedback' => 'nullable|string',
            'internal_rating' => 'nullable|integer|min:1|max:5',
            'follow_up_required' => 'boolean',
            'follow_up_notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $completionData = [
            'actual_expenses' => $request->actual_expenses,
            'client_feedback' => $request->client_feedback,
            'internal_rating' => $request->internal_rating,
            'follow_up_required' => $request->has('follow_up_required'),
        ];

        if ($request->follow_up_notes) {
            $trip->follow_up_notes = $request->follow_up_notes;
        }

        if ($trip->complete($completionData)) {
            return redirect()->route('clients.trips.standalone.show', $trip)
                ->with('success', 'Trip completed successfully.');
        } else {
            return redirect()->back()
                ->with('error', 'Trip cannot be completed in its current status.');
        }
    }

    /**
     * Cancel the trip
     */
    public function cancel(Request $request, ClientTrip $trip)
    {
        $this->authorize('update', $trip);

        if ($trip->cancel($request->get('reason'))) {
            return redirect()->route('clients.trips.standalone.show', $trip)
                ->with('success', 'Trip cancelled successfully.');
        } else {
            return redirect()->back()
                ->with('error', 'Trip cannot be cancelled in its current status.');
        }
    }

    /**
     * Submit trip for reimbursement
     */
    public function submitReimbursement(Request $request, ClientTrip $trip)
    {
        $this->authorize('update', $trip);

        $validator = Validator::make($request->all(), [
            'reimbursement_amount' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        if ($trip->submitForReimbursement($request->reimbursement_amount)) {
            return redirect()->route('clients.trips.standalone.show', $trip)
                ->with('success', 'Trip submitted for reimbursement successfully.');
        } else {
            return redirect()->back()
                ->with('error', 'Trip cannot be submitted for reimbursement.');
        }
    }

    /**
     * Export trips to CSV
     */
    public function export(Request $request)
    {
        $query = ClientTrip::with(['client', 'creator'])
            ->whereHas('client', function ($q) {
                $q->where('company_id', auth()->user()->company_id);
            });

        $this->applyExportFilters($query, $request);

        $trips = $query->orderBy('start_date', 'desc')->get();

        $filename = 'trips_'.date('Y-m-d_H-i-s').'.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        return response()->stream(
            fn() => $this->generateCsvOutput($trips),
            200,
            $headers
        );
    }

    private function applyExportFilters($query, Request $request)
    {
        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('trip_number', 'like', "%{$search}%")
                    ->orWhere('title', 'like', "%{$search}%");
            });
        }

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        if ($clientId = $request->get('client_id')) {
            $query->where('client_id', $clientId);
        }
    }

    private function generateCsvOutput($trips)
    {
        $file = fopen('php://output', 'w');

        fputcsv($file, [
            'Trip Number',
            'Title',
            'Client Name',
            'Destination',
            'Start Date',
            'End Date',
            'Duration (Days)',
            'Status',
            'Trip Type',
            'Transportation',
            'Estimated Expenses',
            'Actual Expenses',
            'Currency',
            'Billable',
            'Reimbursable',
            'Created At',
        ]);

        foreach ($trips as $trip) {
            fputcsv($file, [
                $trip->trip_number,
                $trip->title,
                $trip->client->display_name,
                $trip->formatted_destination,
                $trip->start_date->format('Y-m-d'),
                $trip->end_date->format('Y-m-d'),
                $trip->duration_in_days,
                $trip->status,
                $trip->trip_type,
                $trip->transportation_mode,
                $trip->estimated_expenses ?: 0,
                $trip->actual_expenses ?: 0,
                $trip->currency,
                $trip->billable_to_client ? 'Yes' : 'No',
                $trip->reimbursable ? 'Yes' : 'No',
                $trip->created_at->format('Y-m-d H:i:s'),
            ]);
        }

        fclose($file);
    }

    private function processAttendees(?string $attendeesInput): array
    {
        if (! $attendeesInput) {
            return [];
        }

        $attendees = array_map('trim', explode(',', $attendeesInput));

        return array_filter($attendees);
    }

    private function processExpenseBreakdown(?string $expenseBreakdownInput): array
    {
        if (! $expenseBreakdownInput) {
            return [];
        }

        $expenseBreakdown = [];
        $lines = explode("\n", $expenseBreakdownInput);

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) {
                continue;
            }

            $parts = explode('|', $line);
            if (count($parts) >= 2) {
                $expenseBreakdown[] = [
                    'category' => trim($parts[0]),
                    'amount' => (float) trim($parts[1]),
                    'description' => isset($parts[2]) ? trim($parts[2]) : '',
                ];
            } else {
                $expenseBreakdown[] = [
                    'category' => 'Other',
                    'amount' => 0,
                    'description' => $line,
                ];
            }
        }

        return $expenseBreakdown;
    }

    private function processAttendees(?string $attendeesString): array
    {
        if (! $attendeesString) {
            return [];
        }

        $attendees = array_map('trim', explode(',', $attendeesString));

        return array_filter($attendees);
    }

    private function processExpenseBreakdown(?string $expenseBreakdownString): array
    {
        if (! $expenseBreakdownString) {
            return [];
        }

        $expenseBreakdown = [];
        $lines = explode("\n", $expenseBreakdownString);

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) {
                continue;
            }

            $expenseBreakdown[] = $this->parseExpenseLine($line);
        }

        return $expenseBreakdown;
    }

    private function parseExpenseLine(string $line): array
    {
        $parts = explode('|', $line);

        if (count($parts) >= 2) {
            return [
                'category' => trim($parts[0]),
                'amount' => (float) trim($parts[1]),
                'description' => isset($parts[2]) ? trim($parts[2]) : '',
            ];
        }

        return [
            'category' => 'Other',
            'amount' => 0,
            'description' => $line,
        ];
    }
}
