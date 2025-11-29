<h1>Thank you for your donation!</h1>
<p>Dear {{ $donation->donor_name ?? 'Supporter' }},</p>
<p>We have received your donation of <strong>${{ number_format($donation->amount, 2) }}</strong>.</p>
@if($donation->artist_honoree)
<p>This donation was made in honor of: <strong>{{ $donation->artist_honoree }}</strong></p>
@endif
<p>Your support helps us empower local artists and bring art to the community.</p>
<p>Thank you!<br>Local ARTbeat Team</p>