<?php

namespace App\Http\Controllers;

use App\Models\Internship;
use App\Services\AI\AiService;
use App\Services\AI\DTOs\AiMessage;
use App\Services\AI\Enums\AiRole;
use Illuminate\Http\Request;

class AiPublicController extends Controller
{
    public function __construct(protected AiService $aiService) {}

    public function internshipFinder(Request $request)
    {
        $request->validate([
            'prompt' => 'required|string|max:500',
        ]);

        // 1. Fetch published active internships
        $internships = Internship::published()->with('company')->get();

        $internshipsList = [];
        foreach ($internships as $item) {
            $internshipsList[] = [
                'id' => $item->id,
                'title' => $item->title,
                'slug' => $item->slug,
                'company' => $item->company->name ?? 'Unknown Company',
                'location' => $item->location,
                'type' => $item->type,
                'requirements' => implode(', ', (array) ($item->requirements ?? [])),
            ];
        }

        $internshipsJson = json_encode($internshipsList, JSON_PRETTY_PRINT);

        // 2. Prepare structured system prompt for Gemini
        $systemPrompt = "You are InternHub's AI Internship Matcher. Below is the list of active internships in our database:\n".
            $internshipsJson."\n\n".
            "Analyze the student's input (major, interests, skills) and recommend the top 3 matching internships.\n".
            "For each match, calculate a 'match_score' (integer between 0 and 100) and write an encouraging, brief 'explanation' (1-2 sentences in Indonesian) why it's a great match.\n\n".
            "You MUST respond in raw JSON format matching this exact schema:\n".
            "{\n".
            "  \"matches\": [\n".
            "    {\n".
            "      \"id\": 1,\n".
            "      \"title\": \"Vacancy Title\",\n".
            "      \"slug\": \"slug-url\",\n".
            "      \"company\": \"Company Name\",\n".
            "      \"location\": \"Location\",\n".
            "      \"type\": \"Type\",\n".
            "      \"match_score\": 90,\n".
            "      \"explanation\": \"...\"\n".
            "    }\n".
            "  ]\n".
            "}\n\n".
            'If no suitable matches are found, return {"matches": []}. Do not include markdown formatting (like ```json). Respond only with raw JSON.';

        $messages = [
            new AiMessage(AiRole::SYSTEM, $systemPrompt),
            new AiMessage(AiRole::USER, $request->prompt),
        ];

        try {
            $response = $this->aiService->chat($messages, [
                'skip_auth' => true,
                'rate_limit_key' => 'public-finder:'.$request->ip(),
                'max_requests' => 20, // generous rate limit for guests
            ]);

            $content = trim($response->content);
            // Clean code blocks if present
            if (str_starts_with($content, '```')) {
                $content = preg_replace('/^```(?:json)?\s+|\s+```$/', '', $content);
            }

            $data = json_decode($content, true);
            if ($data && isset($data['matches'])) {
                return response()->json($data);
            }
        } catch (\Exception $e) {
            if ($e->getMessage() === 'AI rate limit exceeded. Please try again later.') {
                return response()->json(['error' => 'RATE_LIMIT_EXCEEDED', 'message' => $e->getMessage()], 429);
            }

            // High-fidelity fallback to local keyword matching engine if Gemini is unavailable
            $promptLower = strtolower($request->prompt);
            $localMatches = [];

            foreach ($internships as $item) {
                $titleLower = strtolower($item->title);
                $reqsLower = strtolower(implode(' ', (array) ($item->requirements ?? [])));

                $score = 0;
                $matchingKeywords = [];

                // Keyword mapping rules for student majors/skills
                $keywords = [
                    'frontend' => ['frontend', 'react', 'vue', 'tailwind', 'css', 'html', 'js', 'javascript'],
                    'software' => ['software', 'coder', 'developer', 'back', 'php', 'laravel', 'golang', 'node', 'database'],
                    'ui' => ['ui', 'ux', 'design', 'figma', 'product design', 'visual', 'prototype'],
                    'marketing' => ['marketing', 'sales', 'social media', 'content', 'copywriter'],
                ];

                foreach ($keywords as $key => $syns) {
                    foreach ($syns as $syn) {
                        if (str_contains($promptLower, $syn) && (str_contains($titleLower, $syn) || str_contains($reqsLower, $syn))) {
                            $score += 15;
                            $matchingKeywords[] = $syn;
                        }
                    }
                }

                if ($score > 0 || str_contains($titleLower, substr($promptLower, 0, 5))) {
                    $finalScore = min(95, 75 + $score);
                    $localMatches[] = [
                        'id' => $item->id,
                        'title' => $item->title,
                        'slug' => $item->slug,
                        'company' => $item->company->name ?? 'Unknown Company',
                        'location' => $item->location,
                        'type' => $item->type,
                        'match_score' => $finalScore,
                        'explanation' => 'Keahlian Anda dalam '.(empty($matchingKeywords) ? 'Teknologi' : implode(', ', array_unique(array_slice($matchingKeywords, 0, 3)))).' sangat cocok dengan kualifikasi yang dicari oleh '.($item->company->name ?? 'perusahaan').' untuk posisi '.$item->title.'.',
                    ];
                }
            }

            // Sort by match score descending
            usort($localMatches, function ($a, $b) {
                return $b['match_score'] <=> $a['match_score'];
            });
            $localMatches = array_slice($localMatches, 0, 3);

            // If no keyword matches found, return top active internships as popular options
            if (empty($localMatches) && count($internships) > 0) {
                foreach ($internships->take(2) as $item) {
                    $localMatches[] = [
                        'id' => $item->id,
                        'title' => $item->title,
                        'slug' => $item->slug,
                        'company' => $item->company->name ?? 'Unknown Company',
                        'location' => $item->location,
                        'type' => $item->type,
                        'match_score' => 85,
                        'explanation' => 'Posisi '.$item->title.' di '.($item->company->name ?? 'perusahaan').' adalah lowongan magang populer yang sangat cocok untuk mengasah keterampilan praktis Anda.',
                    ];
                }
            }

            return response()->json([
                'matches' => $localMatches,
                'message' => 'Matched using high-fidelity local keyword engine (Gemini quota exhausted).',
            ]);
        }

        return response()->json([
            'matches' => [],
            'message' => 'Failed to parse AI response.',
        ]);
    }

    public function faq(Request $request)
    {
        $request->validate([
            'question' => 'required|string|max:500',
        ]);

        $messages = [
            new AiMessage(AiRole::SYSTEM, "You are InternHub's FAQ Assistant. You answer questions about how InternHub works, application processes, and requirements. If you don't know the answer, refer the user to the support email. Do not invent policies."),
            new AiMessage(AiRole::USER, $request->question),
        ];

        try {
            $response = $this->aiService->chat($messages, [
                'skip_auth' => true,
                'rate_limit_key' => 'public-faq:'.$request->ip(),
                'max_requests' => 15,
            ]);

            return response()->json([
                'answer' => $response->content,
                'human_review_required' => false,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 429);
        }
    }
}
