<x-mail::message>
# New enquiry

**{{ $enquiry->name }}** got in touch through the website.

<x-mail::panel>
{{ $enquiry->message }}
</x-mail::panel>

**Subject:** {{ $enquiry->subject }}
**Email:** {{ $enquiry->email }}
**Received:** {{ $enquiry->created_at->siteTime()->format('M d, Y \a\t H:i') }}

<x-mail::button :url="route('admin.messages')">
Open in the dashboard
</x-mail::button>

Replying to this email goes straight to {{ $enquiry->name }}.

Thanks,<br>
{{ config('site.name') }}
</x-mail::message>
