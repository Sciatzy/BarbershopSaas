<?php
namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    public function edit()
    {
        return view('customer.profile.edit', ['user' => auth()->user()]);
    }

    public function update(Request $request)
    {
        $tenantId = (string) (auth()->user()?->tenant_id ?? '');

        $validated = $request->validate([
            'name'  => ['required', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => [
                'required',
                'email',
                Rule::unique('users', 'email')
                    ->where(fn ($query) => $query->where('tenant_id', $tenantId))
                    ->ignore(auth()->id()),
            ],
        ]);

        auth()->user()->update($validated);

        return redirect()->route('customer.profile')->with('success', 'Profile updated.');
    }
}
