<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GeneralSettingsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            // Company Information
            'company_name' => 'required|string|max:255',
            'company_logo' => 'nullable|string|max:255',
            'company_colors' => 'nullable|array',
            'company_colors.primary' => 'nullable|string|regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/',
            'company_colors.secondary' => 'nullable|string|regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/',
            'company_colors.accent' => 'nullable|string|regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/',
            
            // Contact Information
            'business_address' => 'nullable|string|max:500',
            'business_phone' => 'nullable|string|max:50',
            'business_email' => 'nullable|email|max:255',
            'website' => 'nullable|url|max:255',
            'tax_id' => 'nullable|string|max:50',
            
            // Business Operations
            'business_hours' => 'nullable|array',
            'business_hours.monday' => 'nullable|array',
            'business_hours.monday.start' => 'nullable|date_format:H:i',
            'business_hours.monday.end' => 'nullable|date_format:H:i|after:business_hours.monday.start',
            'business_hours.monday.enabled' => 'boolean',
            'business_hours.tuesday' => 'nullable|array',
            'business_hours.tuesday.start' => 'nullable|date_format:H:i',
            'business_hours.tuesday.end' => 'nullable|date_format:H:i|after:business_hours.tuesday.start',
            'business_hours.tuesday.enabled' => 'boolean',
            'business_hours.wednesday' => 'nullable|array',
            'business_hours.wednesday.start' => 'nullable|date_format:H:i',
            'business_hours.wednesday.end' => 'nullable|date_format:H:i|after:business_hours.wednesday.start',
            'business_hours.wednesday.enabled' => 'boolean',
            'business_hours.thursday' => 'nullable|array',
            'business_hours.thursday.start' => 'nullable|date_format:H:i',
            'business_hours.thursday.end' => 'nullable|date_format:H:i|after:business_hours.thursday.start',
            'business_hours.thursday.enabled' => 'boolean',
            'business_hours.friday' => 'nullable|array',
            'business_hours.friday.start' => 'nullable|date_format:H:i',
            'business_hours.friday.end' => 'nullable|date_format:H:i|after:business_hours.friday.start',
            'business_hours.friday.enabled' => 'boolean',
            'business_hours.saturday' => 'nullable|array',
            'business_hours.saturday.start' => 'nullable|date_format:H:i',
            'business_hours.saturday.end' => 'nullable|date_format:H:i|after:business_hours.saturday.start',
            'business_hours.saturday.enabled' => 'boolean',
            'business_hours.sunday' => 'nullable|array',
            'business_hours.sunday.start' => 'nullable|date_format:H:i',
            'business_hours.sunday.end' => 'nullable|date_format:H:i|after:business_hours.sunday.start',
            'business_hours.sunday.enabled' => 'boolean',
            
            'company_holidays' => 'nullable|array',
            'company_holidays.*' => 'date_format:Y-m-d',
            
            // Localization
            'company_language' => 'required|string|size:2|in:en,es,fr,de,it,pt,nl,pl,ru,ja,zh,ko,ar,hi,sv,no,da,fi,tr,el,cs,hu,ro,bg,hr,sl,sk,lt,lv,et,mt,ga,cy,is,fo,gl,eu,ca,gl,an,ast,co,gd,br,kw,gv',
            'company_currency' => 'required|string|size:3|in:USD,EUR,GBP,CAD,AUD,JPY,CHF,CNY,INR,BRL,MXN,KRW,SGD,HKD,SEK,NOK,DKK,PLN,CZK,HUF,RUB,TRY,ZAR,NZD,ILS,AED,SAR,THB,MYR,PHP,IDR,VND,EGP,MAD,KES,GHS,NGN,ZMW,BWP,SZL,LSL,NAD,MZN,AOA,XOF,XAF,KMF,DJF,ERN,ETB,GMD,GNF,LRD,SLL,STD,CVE,MRU,SHP,XPF,TOP,WST,VUV,SBD,FJD,PGK,TVD,KID,AUD,NZD',
            'timezone' => 'required|string|max:255',
            'date_format' => 'required|string|in:Y-m-d,m/d/Y,d/m/Y,M d\\, Y,d M Y,j F Y',
            
            // Custom Fields
            'custom_fields' => 'nullable|array',
            'custom_fields.*.name' => 'string|max:100',
            'custom_fields.*.type' => 'string|in:text,number,email,date,boolean,select,textarea',
            'custom_fields.*.required' => 'boolean',
            'custom_fields.*.options' => 'nullable|array',
            
            // Theme and Display
            'theme' => 'required|string|in:blue,green,red,purple,orange,dark,light,corporate',
            'start_page' => 'required|string|max:255',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'company_name.required' => 'Company name is required.',
            'company_colors.primary.regex' => 'Primary color must be a valid hex color code.',
            'company_colors.secondary.regex' => 'Secondary color must be a valid hex color code.',
            'company_colors.accent.regex' => 'Accent color must be a valid hex color code.',
            'company_country.in' => 'Please select a valid country.',
            'company_website.url' => 'Website must be a valid URL.',
            'business_hours.*.end.after' => 'End time must be after start time.',
            'company_language.in' => 'Please select a valid language.',
            'company_currency.in' => 'Please select a valid currency.',
            'date_format.in' => 'Please select a valid date format.',
            'theme.in' => 'Please select a valid theme.',
        ];
    }

    /**
     * Get custom attribute names for validator errors.
     */
    public function attributes(): array
    {
        return [
            'company_name' => 'company name',
            'company_logo' => 'company logo',
            'business_address' => 'business address',
            'business_phone' => 'business phone',
            'business_email' => 'business email',
            'website' => 'website',
            'tax_id' => 'tax ID',
            'company_language' => 'language',
            'company_currency' => 'currency',
            'date_format' => 'date format',
        ];
    }
}