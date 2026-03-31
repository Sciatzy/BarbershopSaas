<x-mail::message>
# Appointment Confirmed

Your booking is locked in. Here are your appointment details:

<x-mail::panel>
<x-mail::table>
| Detail | Value |
| :----- | :---- |
| Barber Name | {{ $barberName }} |
| Service Type | {{ $serviceType }} |
| Date | {{ $date }} |
| Time | {{ $time }} |
</x-mail::table>
</x-mail::panel>

Please arrive 10 minutes before your schedule to make check-in easier.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
