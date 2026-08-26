<x-mail::message>
# Thank you for getting in touch

Hi {{ $enquiry->name }},

We have received your enquiry and one of our team will get back to you shortly.

<x-mail::panel>
**{{ $enquiry->subject }}**

{{ $enquiry->message }}
</x-mail::panel>

If anything has changed in the meantime, just reply to this email.

<x-mail::button :url="route('home')">
Visit our website
</x-mail::button>

Thanks,<br>
{{ config('site.name') }}<br>
{{ \App\Models\Setting::get('contact_phone') }}
</x-mail::message>
