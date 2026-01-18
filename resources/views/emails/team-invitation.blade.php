<x-mail::message>
# Team Invitation

Hello!

**{{ $inviterName }}** has invited you to join the **{{ $teamName }}** team on DevFlow Pro as a **{{ $role }}**.

<x-mail::button :url="$acceptUrl">
Accept Invitation
</x-mail::button>

This invitation will expire on {{ $expiresAt }}.

If you did not expect to receive an invitation to this team, you may discard this email.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
