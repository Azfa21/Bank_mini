<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ImportCustomersRequest;
use App\Http\Requests\Admin\StoreCustomerRequest;
use App\Http\Requests\Admin\UpdateCustomerRequest;
use App\Models\Customer;
use App\Services\CustomerImportService;
use App\Services\CustomerRegistrationService;
use Illuminate\View\View;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CustomerController extends Controller
{
    public function __construct(
        protected CustomerRegistrationService $registrations,
        protected CustomerImportService $imports,
    ) {}

    public function index()
    {
        $customers = Customer::with('account')
            ->when(request('status'), fn ($q, $status) => $q->where('status', $status))
            ->when(request('search'), function ($query, $search) {
                $search = trim($search);

                if ($search !== '') {
                    $query->where(function ($sub) use ($search) {
                        $sub->where('full_name', 'like', "%{$search}%")
                            ->orWhere('student_number', 'like', "%{$search}%")
                            ->orWhere('class_name', 'like', "%{$search}%");
                    });
                }
            })
            ->latest()
            ->paginate(15);

        if ($this->wantsJson()) {
            return response()->json([
                'success' => true,
                'data' => $customers,
            ]);
        }

        return view('admin.customers.index', compact('customers'));
    }

    public function create(): View
    {
        return view('admin.customers.create');
    }

    public function store(StoreCustomerRequest $request)
    {
        $customer = $this->registrations->register([
            'full_name' => $request->full_name,
            'class_name' => $request->class_name,
            'phone_number' => $request->phone_number,
            'email' => $request->email,
            'pin' => $request->pin,
        ], $request->user()->id);

        if ($this->wantsJson($request)) {
            return response()->json([
                'success' => true,
                'message' => 'Profil nasabah dan rekening berhasil dibuat.',
                'data' => $customer->load('account'),
            ], 201);
        }

        return redirect()->route('admin.customers.index')->with('success', 'Profil nasabah dan rekening berhasil dibuat.');
    }

    public function edit(Customer $customer): View
    {
        return view('admin.customers.edit', ['customer' => $customer->load('account')]);
    }

    public function update(UpdateCustomerRequest $request, Customer $customer)
    {
        $data = $request->only(['student_number', 'full_name', 'class_name', 'phone_number', 'email', 'status']);

        if ($request->filled('pin')) {
            $data['pin_hash'] = $request->pin;
        }

        $customer->update($data);

        if ($this->wantsJson($request)) {
            return response()->json([
                'success' => true,
                'message' => 'Profil nasabah berhasil diperbarui.',
                'data' => $customer->fresh('account'),
            ]);
        }

        return redirect()->route('admin.customers.index')->with('success', 'Profil nasabah berhasil diperbarui.');
    }

    public function toggleStatus(Customer $customer)
    {
        $customer->update(['status' => $customer->status === 'active' ? 'blocked' : 'active']);

        $label = $customer->status === 'active' ? 'diaktifkan' : 'diblokir';

        if ($this->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Nasabah {$customer->full_name} berhasil {$label}.",
                'data' => $customer->fresh(),
            ]);
        }

        return redirect()->route('admin.customers.index')->with('success', "Nasabah {$customer->full_name} berhasil {$label}.");
    }

    public function importForm(): View
    {
        return view('admin.customers.import');
    }

    public function import(ImportCustomersRequest $request)
    {
        try {
            $summary = $this->imports->importFromCsv(
                $request->file('file')->getRealPath(),
                (int) $request->user()->id,
            );
        } catch (InvalidArgumentException $e) {
            if ($this->wantsJson($request)) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 422);
            }

            return back()->with('error', $e->getMessage());
        }

        if ($this->wantsJson($request)) {
            return response()->json([
                'success' => true,
                'message' => "{$summary['imported']} nasabah berhasil diimpor.",
                'data' => $summary,
            ]);
        }

        return redirect()
            ->route('admin.customers.import.form')
            ->with('import_summary', $summary);
    }

    public function importTemplate(): StreamedResponse
    {
        return response()->streamDownload(function () {
            $handle = fopen('php://output', 'wb');

            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, ['nis', 'nama', 'kelas', 'no_hp', 'email']);
            fputcsv($handle, ['20260001', 'Budi Santoso', 'VII A', '081234567001', 'budi.santoso@example.test']);
            fputcsv($handle, ['20260002', 'Citra Lestari', 'VII B', '081234567002', 'citra.lestari@example.test']);

            fclose($handle);
        }, 'template-import-nasabah.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
