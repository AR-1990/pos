<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;

class SystemSettingsController extends Controller
{
    public function index()
    {
        $settings = Setting::first();
        $data = $this->getData();
        return view('system-settings', [
            'pageTitle' => 'Settings',
            'settings' => $settings,
            'countries' => $data['countries'],
            'currencies' => $data['currencies'],
            'timezones' => $data['timezones'],
        ]);
    }

    public function update(Request $request)
    {
        $request->validate([
            'company_name' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'country' => 'nullable|string|max:255',
            'currency' => 'nullable|string|max:10',
            'timezone' => 'nullable|string|max:255',
            'company_logo' => 'nullable|file|image|max:2048',
        ]);

        $settings = Setting::firstOrNew();

        $settings->fill($request->except('company_logo'));

        if ($request->hasFile('company_logo')) {
            $file = $request->file('company_logo');
            $filename = time() . '_' . $file->getClientOriginalName();
            $file->move(public_path('uploads/company_logos'), $filename);
            $settings->company_logo = 'uploads/company_logos/' . $filename;
        }

        $settings->save();

        return redirect()->back()->with('success', 'Settings updated successfully.');
    }

    private function getData()
    {
        $countries = [
            'Afghanistan' => 'Asia/Kabul',
            'Albania' => 'Europe/Tirane',
            'Algeria' => 'Africa/Algiers',
            'Argentina' => 'America/Argentina/Buenos_Aires',
            'Australia' => 'Australia/Sydney',
            'Austria' => 'Europe/Vienna',
            'Bahrain' => 'Asia/Bahrain',
            'Bangladesh' => 'Asia/Dhaka',
            'Belgium' => 'Europe/Brussels',
            'Brazil' => 'America/Sao_Paulo',
            'Canada' => 'America/Toronto',
            'China' => 'Asia/Shanghai',
            'Denmark' => 'Europe/Copenhagen',
            'Egypt' => 'Africa/Cairo',
            'Finland' => 'Europe/Helsinki',
            'France' => 'Europe/Paris',
            'Germany' => 'Europe/Berlin',
            'Greece' => 'Europe/Athens',
            'Hong Kong' => 'Asia/Hong_Kong',
            'India' => 'Asia/Kolkata',
            'Indonesia' => 'Asia/Jakarta',
            'Iran' => 'Asia/Tehran',
            'Iraq' => 'Asia/Baghdad',
            'Ireland' => 'Europe/Dublin',
            'Italy' => 'Europe/Rome',
            'Japan' => 'Asia/Tokyo',
            'Kuwait' => 'Asia/Kuwait',
            'Malaysia' => 'Asia/Kuala_Lumpur',
            'Mexico' => 'America/Mexico_City',
            'Netherlands' => 'Europe/Amsterdam',
            'New Zealand' => 'Pacific/Auckland',
            'Nigeria' => 'Africa/Lagos',
            'Norway' => 'Europe/Oslo',
            'Oman' => 'Asia/Muscat',
            'Pakistan' => 'Asia/Karachi',
            'Philippines' => 'Asia/Manila',
            'Poland' => 'Europe/Warsaw',
            'Portugal' => 'Europe/Lisbon',
            'Qatar' => 'Asia/Qatar',
            'Russia' => 'Europe/Moscow',
            'Saudi Arabia' => 'Asia/Riyadh',
            'Singapore' => 'Asia/Singapore',
            'South Africa' => 'Africa/Johannesburg',
            'South Korea' => 'Asia/Seoul',
            'Spain' => 'Europe/Madrid',
            'Sri Lanka' => 'Asia/Colombo',
            'Sweden' => 'Europe/Stockholm',
            'Switzerland' => 'Europe/Zurich',
            'Thailand' => 'Asia/Bangkok',
            'Turkey' => 'Europe/Istanbul',
            'Ukraine' => 'Europe/Kiev',
            'United Arab Emirates' => 'Asia/Dubai',
            'United Kingdom' => 'Europe/London',
            'United States' => 'America/New_York',
            'Vietnam' => 'Asia/Ho_Chi_Minh',
        ];

        $currencies = [
            'USD' => 'United States Dollar',
            'EUR' => 'Euro',
            'GBP' => 'British Pound Sterling',
            'JPY' => 'Japanese Yen',
            'AUD' => 'Australian Dollar',
            'CAD' => 'Canadian Dollar',
            'CHF' => 'Swiss Franc',
            'CNY' => 'Chinese Yuan',
            'HKD' => 'Hong Kong Dollar',
            'NZD' => 'New Zealand Dollar',
            'SEK' => 'Swedish Krona',
            'KRW' => 'South Korean Won',
            'SGD' => 'Singapore Dollar',
            'NOK' => 'Norwegian Krone',
            'MXN' => 'Mexican Peso',
            'INR' => 'Indian Rupee',
            'RUB' => 'Russian Ruble',
            'ZAR' => 'South African Rand',
            'TRY' => 'Turkish Lira',
            'BRL' => 'Brazilian Real',
            'PKR' => 'Pakistani Rupee',
            'IDR' => 'Indonesian Rupiah',
            'SAR' => 'Saudi Riyal',
            'AED' => 'United Arab Emirates Dirham',
            'THB' => 'Thai Baht',
            'MYR' => 'Malaysian Ringgit',
            'VND' => 'Vietnamese Dong',
            'EGP' => 'Egyptian Pound',
            'PLN' => 'Polish Zloty',
            'PHP' => 'Philippine Peso',
            'HUF' => 'Hungarian Forint',
            'CZK' => 'Czech Koruna',
            'ILS' => 'Israeli New Shekel',
            'CLP' => 'Chilean Peso',
            'KWD' => 'Kuwaiti Dinar',
        ];
        
        // Sort arrays
        ksort($countries);
        ksort($currencies);
        
        // Comprehensive list of timezones
        $timezones = \DateTimeZone::listIdentifiers();

        return compact('countries', 'currencies', 'timezones');
    }
}
