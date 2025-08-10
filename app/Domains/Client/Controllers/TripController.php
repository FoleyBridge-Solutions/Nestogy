<?php

namespace App\Domains\Client\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\ClientTrip;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class TripController extends Controller
{
    /**
     * Display a listing of all trips (standalone view)
     */
    public function index(Request $request)
    {
        $query = ClientTrip::with(['client', 'creator', 'approver'])
            ->whereHas('client', function($q) {
                $q->where('tenant_id', auth()->user()->tenant_id);
            });

        // Apply search filters
        if ($search = $request->get('search')) {
            $query->where(function($q) use ($search) {
                $q->where('trip_number', 'like', "%{$search}%")
                  ->orWhere('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('destination_city', 'like', "%{$search}%")
                  ->orWhereHas('client', function($clientQuery) use ($search) {
                      $clientQuery->where('name', 'like', "%{$search}%")
                                  ->orWhere('company_name', 'like', "%{$search}%");
                  });
            });
        }

        // Apply status filter
        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        // Apply client filter
        if ($clientId = $request->get('client_id')) {
            $query->where('client_id', $clientId);
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

        $clients = Client::where('tenant_id', auth()->user()->tenant_id)
                        ->orderBy('name')
                        ->get();

        $statuses = ClientTrip::getStatuses();
        $tripTypes = ClientTrip::getTripTypes();
        $transportationModes = ClientTrip::getTransportationModes();

        return view('clients.trips.index', compact('trips', 'clients', 'statuses', 'tripTypes', 'transportationModes'));
    }

    /**
     * Show the form for creating a new trip
     */
    public function create(Request $request)
    {
        $clients = Client::where('tenant_id', auth()->user()->tenant_id)
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
                    $query->where('tenant_id', auth()->user()->tenant_id);
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
            'status' => 'required|in:' . implode(',', array_keys(ClientTrip::getStatuses())),
            'trip_type' => 'required|in:' . implode(',', array_keys(ClientTrip::getTripTypes())),
            'transportation_mode' => 'required|in:' . implode(',', array_keys(ClientTrip::getTransportationModes())),
            'accommodation_details' => 'nullable|string',
            'estimated_expenses' => 'nullable|numeric|min:0',
            'currency' => 'required|in:' . implode(',', array_keys(ClientTrip::getCurrencies())),
            'mileage' => 'nullable|numeric|min:0',
            'per_diem_amount' => 'nullable|numeric|min:0',
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

        // Process attendees
        $attendees = [];
        if ($request->attendees) {
            $attendees = array_map('trim', explode(',', $request->attendees));
            $attendees = array_filter($attendees);
        }

        // Process expense breakdown
        $expenseBreakdown = [];
        if ($request->expense_breakdown) {
            $lines = explode("\n", $request->expense_breakdown);
            foreach ($lines as $line) {
                $line = trim($line);
                if (!empty($line)) {
                    // Try to parse "Category | Amount | Description" format
                    $parts = explode('|', $line);
                    if (count($parts) >= 2) {
                        $expenseBreakdown[] = [
                            'category' => trim($parts[0]),
                            'amount' => (float) trim($parts[1]),
                            'description' => isset($parts[2]) ? trim($parts[2]) : ''
                        ];
                    } else {
                        $expenseBreakdown[] = [
                            'category' => 'Other',
                            'amount' => 0,
                            'description' => $line
                        ];
                    }
                }
            }
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
            'attendees' => $attendees,
            'notes' => $request->notes,
            'expense_breakdown' => $expenseBreakdown,
            'created_by' => auth()->id(),
        ]);
        
        $trip->tenant_id = auth()->user()->tenant_id;
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

        $clients = Client::where('tenant_id', auth()->user()->tenant_id)
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
                    $query->where('tenant_id', auth()->user()->tenant_id);
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
            'status' => 'required|in:' . implode(',', array_keys(ClientTrip::getStatuses())),
            'trip_type' => 'required|in:' . implode(',', array_keys(ClientTrip::getTripTypes())),
            'transportation_mode' => 'required|in:' . implode(',', array_keys(ClientTrip::getTransportationModes())),
            'accommodation_details' => 'nullable|string',
            'estimated_expenses' => 'nullable|numeric|min:0',
            'actual_expenses' => 'nullable|numeric|min:0',
            'currency' => 'required|in:' . implode(',', array_keys(ClientTrip::getCurrencies())),
            'mileage' => 'nullable|numeric|min:0',
            'per_diem_amount' => 'nullable|numeric|min:0',
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

        // Process attendees
        $attendees = [];
        if ($request->attendees) {
            $attendees = array_map('trim', explode(',', $request->attendees));
            $attendees = array_filter($attendees);
        }

        // Process expense breakdown
        $expenseBreakdown = [];
        if ($request->expense_breakdown) {
            $lines = explode("\n", $request->expense_breakdown);
            foreach ($lines as $line) {
                $line = trim($line);
                if (!empty($line)) {
                    // Try to parse "Category | Amount | Description" format
                    $parts = explode('|', $line);
                    if (count($parts) >= 2) {
                        $expenseBreakdown[] = [
                            'category' => trim($parts[0]),
                            'amount' => (float) trim($parts[1]),
                            'description' => isset($parts[2]) ? trim($parts[2]) : ''
                        ];
                    } else {
                        $expenseBreakdown[] = [
                            'category' => 'Other',
                            'amount' => 0,
                            'description' => $line
                        ];
                    }
                }
            }
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
            'attendees' => $attendees,
            'notes' => $request->notes,
            'client_feedback' => $request->client_feedback,
            'internal_rating' => $request->internal_rating,
            'expense_breakdown' => $expenseBreakdown,
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
            'actual_expenses' => 'nullable|numeric|min:0',
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
            ->whereHas('client', function($q) {
                $q->where('tenant_id', auth()->user()->tenant_id);
            });

        // Apply same filters as index
        if ($search = $request->get('search')) {
            $query->where(function($q) use ($search) {
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

        $trips = $query->orderBy('start_date', 'desc')->get();

        $filename = 'trips_' . date('Y-m-d_H-i-s') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function() use ($trips) {
            $file = fopen('php://output', 'w');
            
            // CSV headers
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
                'Created At'
            ]);

            // CSV data
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
        };

        return response()->stream($callback, 200, $headers);
    }
}