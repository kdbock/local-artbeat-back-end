<h1>Confirm Your Subscription</h1>
<p>Hi {{ $subscriber->name ?? 'there' }},</p>
<p>Thank you for signing up for the Local ARTbeat newsletter!</p>
<p>Please confirm your subscription by clicking the link below:</p>
<p><a href="{{ url('/newsletter/confirm?token=' . $subscriber->confirmation_token) }}">Confirm Subscription</a></p>
<p>If you did not sign up, you can ignore this email.</p>
<p>Thank you!<br>Local ARTbeat Team</p>