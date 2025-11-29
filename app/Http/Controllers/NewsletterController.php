<?php

namespace App\Http\Controllers;

use App\Models\NewsletterSubscriber;
use App\Models\NewsletterTag;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class NewsletterController extends Controller
{
    public function subscribe(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'name' => 'nullable|string|max:255',
            'tags' => 'nullable|array',
        ]);

        $existingSubscriber = NewsletterSubscriber::withTrashed()->where('email', $validated['email'])->first();

        if ($existingSubscriber && !$existingSubscriber->trashed()) {
            return response()->json(['message' => 'Already subscribed', 'subscriber_id' => $existingSubscriber->id]);
        }

        if ($existingSubscriber && $existingSubscriber->trashed()) {
            $subscriber = $existingSubscriber;
            $subscriber->restore();
        } else {
            $subscriber = NewsletterSubscriber::create([
                'email' => $validated['email'],
                'name' => $validated['name'] ?? null,
                'confirmed' => false,
                'status' => 'pending',
                'source' => 'signup_form',
                'confirmation_token' => bin2hex(random_bytes(16)),
            ]);
        }

        if (isset($validated['tags']) && is_array($validated['tags'])) {
            $tags = NewsletterTag::whereIn('slug', $validated['tags'])->pluck('id')->toArray();
            $subscriber->tags()->sync($tags);
        }

        Mail::to($subscriber->email)->send(new \App\Mail\NewsletterConfirmationMail($subscriber));
        return response()->json(['subscribed' => true, 'subscriber_id' => $subscriber->id]);
    }

    public function confirmEmail(Request $request)
    {
        $validated = $request->validate([
            'token' => 'required|string',
        ]);

        $subscriber = NewsletterSubscriber::where('confirmation_token', $validated['token'])->firstOrFail();
        $subscriber->confirmEmail();

        return response()->json(['confirmed' => true, 'subscriber' => $subscriber]);
    }

    public function index(Request $request)
    {
        $user = Auth::user();
        if (!$user || !($user->isAdmin() || $user->isEditor())) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $query = NewsletterSubscriber::with(['tags', 'segments']);

        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->has('confirmed')) {
            $confirmed = filter_var($request->input('confirmed'), FILTER_VALIDATE_BOOLEAN);
            $query->where('confirmed', $confirmed);
        }

        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('email', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%");
            });
        }

        if ($request->has('tag')) {
            $query->withTag($request->input('tag'));
        }

        if ($request->has('segment')) {
            $query->withSegment($request->input('segment'));
        }

        $query->orderBy('created_at', 'desc');
        $subscribers = $request->has('paginate') ? $query->paginate(50) : $query->get();

        return response()->json(['subscribers' => $subscribers]);
    }

    public function show($id)
    {
        $user = Auth::user();
        if (!$user || !($user->isAdmin() || $user->isEditor())) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $subscriber = NewsletterSubscriber::with(['tags', 'segments', 'campaignRecipients'])->findOrFail($id);
        return response()->json($subscriber);
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        if (!$user || !($user->isAdmin() || $user->isEditor())) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'email' => 'required|email|unique:newsletter_subscribers,email',
            'name' => 'nullable|string|max:255',
            'interests' => 'nullable|string',
            'confirmed' => 'sometimes|boolean',
            'tags' => 'nullable|array',
            'tags.*' => 'integer|exists:newsletter_tags,id',
            'custom_fields' => 'nullable|json',
        ]);

        $validated['confirmed'] = $validated['confirmed'] ?? false;
        $validated['status'] = 'subscribed';
        $validated['source'] = 'manual';

        if (!$validated['confirmed']) {
            $validated['confirmation_token'] = bin2hex(random_bytes(16));
        } else {
            $validated['confirmed_at'] = now();
        }

        $subscriber = NewsletterSubscriber::create($validated);

        if (isset($validated['tags']) && is_array($validated['tags'])) {
            $subscriber->tags()->attach($validated['tags']);
        }

        if (!$validated['confirmed']) {
            Mail::to($subscriber->email)->send(new \App\Mail\NewsletterConfirmationMail($subscriber));
        }

        return response()->json($subscriber, 201);
    }

    public function update(Request $request, $id)
    {
        $user = Auth::user();
        if (!$user || !($user->isAdmin() || $user->isEditor())) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $subscriber = NewsletterSubscriber::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'interests' => 'sometimes|nullable|string',
            'confirmed' => 'sometimes|boolean',
            'status' => 'sometimes|in:subscribed,unsubscribed,bounced,invalid',
            'tags' => 'sometimes|array',
            'tags.*' => 'integer|exists:newsletter_tags,id',
            'custom_fields' => 'sometimes|nullable|json',
        ]);

        $tags = $validated['tags'] ?? null;
        unset($validated['tags']);

        $subscriber->update($validated);

        if ($tags !== null) {
            $subscriber->tags()->sync($tags);
        }

        return response()->json($subscriber);
    }

    public function destroy($id)
    {
        $user = Auth::user();
        if (!$user || !($user->isAdmin() || $user->isEditor())) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $subscriber = NewsletterSubscriber::findOrFail($id);
        $subscriber->delete();
        return response()->json(['deleted' => true]);
    }

    public function importCsv(Request $request)
    {
        $user = Auth::user();
        if (!$user || !$user->isAdmin()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'file' => 'required|file|mimes:csv,txt',
            'confirmed' => 'sometimes|boolean',
            'tags' => 'nullable|array',
            'tags.*' => 'integer|exists:newsletter_tags,id',
        ]);

        $file = $validated['file'];
        $confirmed = $validated['confirmed'] ?? true;
        $tags = $validated['tags'] ?? [];

        $imported = 0;
        $skipped = 0;
        $errors = [];

        if (($handle = fopen($file->path(), 'r')) !== false) {
            $header = fgetcsv($handle);
            $emailIndex = array_search('email', array_map('strtolower', $header));
            $nameIndex = array_search('name', array_map('strtolower', $header));

            if ($emailIndex === false) {
                return response()->json(['error' => 'CSV must contain an "email" column'], 400);
            }

            DB::beginTransaction();

            try {
                while (($row = fgetcsv($handle)) !== false) {
                    $email = trim($row[$emailIndex] ?? '');
                    $name = isset($nameIndex) ? trim($row[$nameIndex] ?? '') : null;

                    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                        $skipped++;
                        continue;
                    }

                    $existingSubscriber = NewsletterSubscriber::withTrashed()->where('email', $email)->first();

                    if ($existingSubscriber && !$existingSubscriber->trashed()) {
                        $skipped++;
                        continue;
                    }

                    if ($existingSubscriber && $existingSubscriber->trashed()) {
                        $subscriber = $existingSubscriber;
                        $subscriber->restore();
                    } else {
                        $subscriber = NewsletterSubscriber::create([
                            'email' => $email,
                            'name' => $name,
                            'confirmed' => $confirmed,
                            'status' => $confirmed ? 'subscribed' : 'pending',
                            'source' => 'csv_import',
                            'confirmed_at' => $confirmed ? now() : null,
                            'confirmation_token' => $confirmed ? null : bin2hex(random_bytes(16)),
                        ]);
                    }

                    if (!empty($tags)) {
                        $subscriber->tags()->syncWithoutDetaching($tags);
                    }

                    $imported++;
                }

                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                fclose($handle);
                return response()->json(['error' => 'Import failed: ' . $e->getMessage()], 500);
            }

            fclose($handle);
        }

        return response()->json([
            'imported' => $imported,
            'skipped' => $skipped,
            'errors' => $errors,
        ]);
    }

    public function exportCsv(Request $request)
    {
        $user = Auth::user();
        if (!$user || !($user->isAdmin() || $user->isEditor())) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $query = NewsletterSubscriber::query();

        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->has('confirmed')) {
            $confirmed = filter_var($request->input('confirmed'), FILTER_VALIDATE_BOOLEAN);
            $query->where('confirmed', $confirmed);
        }

        if ($request->has('tag')) {
            $query->withTag($request->input('tag'));
        }

        $subscribers = $query->get(['id', 'email', 'name', 'interests', 'status', 'confirmed', 'created_at']);

        $csv = "ID,Email,Name,Interests,Status,Confirmed,Subscribed Date\n";
        foreach ($subscribers as $subscriber) {
            $csv .= sprintf(
                "%d,\"%s\",\"%s\",\"%s\",%s,%s,%s\n",
                $subscriber->id,
                str_replace('"', '""', $subscriber->email),
                str_replace('"', '""', $subscriber->name ?? ''),
                str_replace('"', '""', $subscriber->interests ?? ''),
                $subscriber->status,
                $subscriber->confirmed ? 'Yes' : 'No',
                $subscriber->created_at->format('Y-m-d H:i:s')
            );
        }

        return response()->stream(
            function () use ($csv) {
                echo $csv;
            },
            200,
            [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="subscribers-' . now()->format('Y-m-d') . '.csv"',
            ]
        );
    }

    public function bulkAddTag(Request $request)
    {
        $user = Auth::user();
        if (!$user || !$user->isAdmin()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'subscriber_ids' => 'required|array',
            'subscriber_ids.*' => 'integer|exists:newsletter_subscribers,id',
            'tag_id' => 'required|integer|exists:newsletter_tags,id',
        ]);

        $tag = NewsletterTag::findOrFail($validated['tag_id']);
        $tag->subscribers()->syncWithoutDetaching($validated['subscriber_ids']);
        $tag->updateSubscriberCount();

        return response()->json(['success' => true, 'tag' => $tag]);
    }

    public function bulkRemoveTag(Request $request)
    {
        $user = Auth::user();
        if (!$user || !$user->isAdmin()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'subscriber_ids' => 'required|array',
            'subscriber_ids.*' => 'integer|exists:newsletter_subscribers,id',
            'tag_id' => 'required|integer|exists:newsletter_tags,id',
        ]);

        $tag = NewsletterTag::findOrFail($validated['tag_id']);
        $tag->subscribers()->detach($validated['subscriber_ids']);
        $tag->updateSubscriberCount();

        return response()->json(['success' => true, 'tag' => $tag]);
    }

    public function bulkChangeStatus(Request $request)
    {
        $user = Auth::user();
        if (!$user || !$user->isAdmin()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'subscriber_ids' => 'required|array',
            'subscriber_ids.*' => 'integer|exists:newsletter_subscribers,id',
            'status' => 'required|in:subscribed,unsubscribed,bounced,invalid',
        ]);

        NewsletterSubscriber::whereIn('id', $validated['subscriber_ids'])
            ->update(['status' => $validated['status']]);

        return response()->json(['success' => true, 'updated' => count($validated['subscriber_ids'])]);
    }
}
