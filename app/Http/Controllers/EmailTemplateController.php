<?php

namespace App\Http\Controllers;

use App\Models\EmailTemplate;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class EmailTemplateController extends Controller
{
    public function index(): JsonResponse
    {
        $templates = EmailTemplate::with(['createdBy', 'updatedBy'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn ($t) => $this->formatTemplate($t));

        return response()->json($templates);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|unique:email_templates',
            'slug' => 'nullable|string|unique:email_templates',
            'description' => 'nullable|string',
            'content_html' => 'required|string',
            'content_blocks' => 'nullable|array',
            'global_styles' => 'nullable|array',
            'is_default' => 'nullable|boolean',
        ]);

        $validated['slug'] = $validated['slug'] ?? Str::slug($validated['name']);

        if ($validated['is_default'] ?? false) {
            EmailTemplate::where('is_default', true)->update(['is_default' => false]);
        }

        $template = EmailTemplate::create($validated);

        return response()->json($this->formatTemplate($template), 201);
    }

    public function show(EmailTemplate $emailTemplate): JsonResponse
    {
        return response()->json($this->formatTemplate($emailTemplate->load(['createdBy', 'updatedBy'])));
    }

    public function update(Request $request, EmailTemplate $emailTemplate): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|unique:email_templates,name,' . $emailTemplate->id,
            'slug' => 'nullable|string|unique:email_templates,slug,' . $emailTemplate->id,
            'description' => 'nullable|string',
            'content_html' => 'required|string',
            'content_blocks' => 'nullable|array',
            'global_styles' => 'nullable|array',
            'is_default' => 'nullable|boolean',
        ]);

        $validated['slug'] = $validated['slug'] ?? Str::slug($validated['name']);

        if (($validated['is_default'] ?? false) && !$emailTemplate->is_default) {
            EmailTemplate::where('is_default', true)->update(['is_default' => false]);
        }

        $emailTemplate->update($validated);

        return response()->json($this->formatTemplate($emailTemplate->refresh()->load(['createdBy', 'updatedBy'])));
    }

    public function destroy(EmailTemplate $emailTemplate): JsonResponse
    {
        $emailTemplate->delete();

        return response()->json(['message' => 'Template deleted successfully']);
    }

    public function clone(EmailTemplate $emailTemplate): JsonResponse
    {
        $newTemplate = $emailTemplate->replicate();
        $newTemplate->name = $emailTemplate->name . ' (Copy)';
        $newTemplate->slug = Str::slug($newTemplate->name) . '-' . time();
        $newTemplate->is_default = false;
        $newTemplate->save();

        return response()->json($this->formatTemplate($newTemplate), 201);
    }

    public function restore(EmailTemplate $emailTemplate): JsonResponse
    {
        $emailTemplate->restore();

        return response()->json($this->formatTemplate($emailTemplate->load(['createdBy', 'updatedBy'])));
    }

    private function formatTemplate(EmailTemplate $template): array
    {
        return [
            'id' => $template->id,
            'name' => $template->name,
            'slug' => $template->slug,
            'description' => $template->description,
            'content_html' => $template->content_html,
            'content_blocks' => $template->content_blocks,
            'global_styles' => $template->global_styles,
            'is_default' => $template->is_default,
            'created_by' => $template->createdBy?->name,
            'created_at' => $template->created_at->toIso8601String(),
            'updated_at' => $template->updated_at->toIso8601String(),
        ];
    }
}
