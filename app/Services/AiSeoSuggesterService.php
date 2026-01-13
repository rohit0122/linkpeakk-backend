<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AiSeoSuggesterService
{
    private $mistralApiKey;

    private $geminiApiKey;

    public function __construct()
    {
        $this->mistralApiKey = config('services.mistral.api_key');
        $this->geminiApiKey = config('services.gemini.api_key');
    }

    /**
     * Generate SEO suggestions (title, description, keywords) using AI waterfall approach.
     * Tries: Mistral → Gemini → Mock
     */
    public function generateSeo(string $title, string $bio, string $slug): array
    {
        // Try Mistral AI first
        if ($this->mistralApiKey) {
            try {
                $result = $this->tryMistralAI($title, $bio, $slug);
                if ($result) {
                    return $result;
                }
            } catch (\Exception $e) {
                Log::warning('Mistral AI SEO generation failed', ['error' => $e->getMessage()]);
            }
        }

        // Fallback to Gemini AI
        if ($this->geminiApiKey) {
            try {
                $result = $this->tryGeminiAI($title, $bio, $slug);
                if ($result) {
                    return $result;
                }
            } catch (\Exception $e) {
                Log::warning('Gemini AI SEO generation failed', ['error' => $e->getMessage()]);
            }
        }

        // Final fallback: Smart mock data
        return $this->generateMock($title, $bio, $slug);
    }

    /**
     * Try Mistral AI for SEO generation.
     */
    private function tryMistralAI(string $title, string $bio, string $slug): ?array
    {
        $prompt = $this->buildPrompt($title, $bio, $slug);

        $client = Http::timeout(10);

        // Disable SSL verification in local environment (fixes cURL error 60 on Windows)
        if (config('app.env') === 'local') {
            $client->withoutVerifying();
        }

        $response = $client->withHeaders([
            'Authorization' => 'Bearer '.$this->mistralApiKey,
            'Content-Type' => 'application/json',
        ])
            ->post('https://api.mistral.ai/v1/chat/completions', [
                'model' => 'mistral-tiny',
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => $prompt,
                    ],
                ],
                'temperature' => 0.7,
                'max_tokens' => 300,
            ]);

        if ($response->successful()) {
            $data = $response->json();
            $content = $data['choices'][0]['message']['content'] ?? null;

            if ($content) {
                $parsed = $this->parseAiResponse($content);
                if ($parsed) {
                    return $parsed;
                }
                Log::warning('Mistral AI SEO parsing failed', ['content' => $content]);
            }
        } else {
            Log::error('Mistral AI SEO request failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
        }

        return null;
    }

    /**
     * Try Gemini AI for SEO generation.
     */
    private function tryGeminiAI(string $title, string $bio, string $slug): ?array
    {
        $prompt = $this->buildPrompt($title, $bio, $slug);

        $client = Http::timeout(10);

        // Disable SSL verification in local environment
        if (config('app.env') === 'local') {
            $client->withoutVerifying();
        }

        $response = $client->withHeaders([
            'Content-Type' => 'application/json',
        ])
            ->post('https://generativelanguage.googleapis.com/v1beta/models/gemini-pro:generateContent?key='.$this->geminiApiKey, [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $prompt],
                        ],
                    ],
                ],
                'generationConfig' => [
                    'temperature' => 0.7,
                    'maxOutputTokens' => 300,
                ],
            ]);

        if ($response->successful()) {
            $data = $response->json();
            $content = $data['candidates'][0]['content']['parts'][0]['text'] ?? null;

            if ($content) {
                $parsed = $this->parseAiResponse($content);
                if ($parsed) {
                    return $parsed;
                }
                Log::warning('Gemini AI SEO parsing failed', ['content' => $content]);
            }
        } else {
            Log::error('Gemini AI SEO request failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
        }

        return null;
    }

    /**
     * Build the AI prompt for SEO generation.
     */
    private function buildPrompt(string $title, string $bio, string $slug): string
    {
        return "Given a user's Bio Page Details:
        - Slug/Username: \"{$slug}\"
        - Title: \"{$title}\"
        - Description: \"{$bio}\"
        
        Return a highly targeted JSON object with optimized SEO metadata:
        1. \"title\": A compelling, search-optimized title (max 60 chars). Incorporate the username if relevant.
        2. \"description\": A concise, engaging meta description (max 160 chars) that highlights the unique value of this profile.
        3. \"keywords\": An array of 5-8 highly relevant and targeted SEO keywords including niche and brand-related terms.
        
        Example JSON:
        { 
            \"title\": \"John Doe (@johndev) | Creative Web Developer & UI Designer\", 
            \"description\": \"Explore projects and insights by John Doe (@johndev), a passionate web developer specializing in building modern, responsive user experiences.\",
            \"keywords\": [\"web developer\", \"UI designer\", \"portfolio\", \"react\", \"frontend\", \"johndev\"]
        }
        
        Return ONLY the JSON object, no additional text.";
    }

    /**
     * Parse AI response.
     */
    private function parseAiResponse(string $content): ?array
    {
        $content = trim($content);
        $content = preg_replace('/```json\s*/', '', $content);
        $content = preg_replace('/```\s*/', '', $content);
        $content = trim($content);

        $decoded = json_decode($content, true);

        if ($decoded && isset($decoded['title']) && isset($decoded['description']) && isset($decoded['keywords'])) {
            return $decoded;
        }

        return null;
    }

    /**
     * Final fallback mock data.
     */
    private function generateMock(string $title, string $bio, string $slug): array
    {
        $displaySlug = $slug ? "(@{$slug})" : '';

        return [
            'title' => trim("{$title} {$displaySlug}").' | Official LinkPeakK Page',
            'description' => $bio ? substr($bio, 0, 150).'...' : 'Check out my official bio page on LinkPeakK.',
            'keywords' => array_filter([$slug, 'bio link', 'profile', 'social media', 'linkpeakk', 'portfolio']),
        ];
    }
}
