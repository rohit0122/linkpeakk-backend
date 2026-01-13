<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AiTitleSuggesterService
{
    private $mistralApiKey;
    private $geminiApiKey;

    public function __construct()
    {
        $this->mistralApiKey = config('services.mistral.api_key');
        $this->geminiApiKey = config('services.gemini.api_key');
    }

    /**
     * Generate title suggestions for a URL using AI waterfall approach.
     * Tries: Mistral → Gemini → Smart Mock
     */
    public function generateTitles(string $url): array
    {
        // Try Mistral AI first
        if ($this->mistralApiKey) {
            try {
                $result = $this->tryMistralAI($url);
                if ($result) {
                    return $result;
                }
            } catch (\Exception $e) {
                Log::warning('Mistral AI failed', ['error' => $e->getMessage()]);
            }
        }

        // Fallback to Gemini AI
        if ($this->geminiApiKey) {
            try {
                $result = $this->tryGeminiAI($url);
                if ($result) {
                    return $result;
                }
            } catch (\Exception $e) {
                Log::warning('Gemini AI failed', ['error' => $e->getMessage()]);
            }
        }

        // Final fallback: Smart mock data
        return $this->generateSmartMock($url);
    }

    /**
     * Try Mistral AI for title generation.
     */
    private function tryMistralAI(string $url): ?array
    {
        $prompt = $this->buildPrompt($url);

        $client = Http::timeout(10);
        
        if (config('app.env') === 'local') {
            $client->withoutVerifying();
        }

        $response = $client->withHeaders([
                'Authorization' => 'Bearer ' . $this->mistralApiKey,
                'Content-Type' => 'application/json',
            ])
            ->post('https://api.mistral.ai/v1/chat/completions', [
                'model' => 'mistral-tiny',
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => $prompt
                    ]
                ],
                'temperature' => 0.7,
                'max_tokens' => 200,
            ]);

        if ($response->successful()) {
            $data = $response->json();
            $content = $data['choices'][0]['message']['content'] ?? null;
            
            if ($content) {
                $parsed = $this->parseAiResponse($content);
                if ($parsed) {
                    return $parsed;
                }
                Log::warning('Mistral AI Title parsing failed', ['content' => $content]);
            }
        } else {
            Log::error('Mistral AI Title request failed', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);
        }

        return null;
    }

    /**
     * Try Gemini AI for title generation.
     */
    private function tryGeminiAI(string $url): ?array
    {
        $prompt = $this->buildPrompt($url);

        $client = Http::timeout(10);
        
        if (config('app.env') === 'local') {
            $client->withoutVerifying();
        }

        $response = $client->withHeaders([
                'Content-Type' => 'application/json',
            ])
            ->post('https://generativelanguage.googleapis.com/v1beta/models/gemini-pro:generateContent?key=' . $this->geminiApiKey, [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $prompt]
                        ]
                    ]
                ],
                'generationConfig' => [
                    'temperature' => 0.7,
                    'maxOutputTokens' => 200,
                ]
            ]);

        if ($response->successful()) {
            $data = $response->json();
            $content = $data['candidates'][0]['content']['parts'][0]['text'] ?? null;
            
            if ($content) {
                $parsed = $this->parseAiResponse($content);
                if ($parsed) {
                    return $parsed;
                }
                Log::warning('Gemini AI Title parsing failed', ['content' => $content]);
            }
        } else {
            Log::error('Gemini AI Title request failed', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);
        }

        return null;
    }

    /**
     * Build the AI prompt for title generation.
     */
    private function buildPrompt(string $url): string
    {
        return "Analyze this URL: \"{$url}\".
        Return a JSON object with two fields:
        1. \"brand\": A short string identifying the platform, brand, or content type.
        2. \"suggestions\": An array of 3 distinct title options:
           - Option 1: Professional & Direct
           - Option 2: Creative & Engaging (with emoji)
           - Option 3: Minimal & Clean
        
        Example JSON:
        { 
            \"brand\": \"GitHub\", 
            \"suggestions\": [
                \"My Professional GitHub Profile\", 
                \"Check out my Open Source Code 💻\", 
                \"My Code\"
            ] 
        }
        
        Return ONLY the JSON object, no additional text.";
    }

    /**
     * Parse AI response and extract brand + suggestions.
     */
    private function parseAiResponse(string $content): ?array
    {
        // Try to extract JSON from response
        $content = trim($content);
        
        // Remove markdown code blocks if present
        $content = preg_replace('/```json\s*/', '', $content);
        $content = preg_replace('/```\s*/', '', $content);
        $content = trim($content);
        
        $decoded = json_decode($content, true);
        
        if ($decoded && isset($decoded['brand']) && isset($decoded['suggestions']) && is_array($decoded['suggestions'])) {
            return [
                'brand' => $decoded['brand'],
                'suggestions' => array_slice($decoded['suggestions'], 0, 3), // Ensure max 3
            ];
        }
        
        return null;
    }

    /**
     * Generate smart mock data based on URL patterns.
     */
    private function generateSmartMock(string $url): array
    {
        $lowerUrl = strtolower($url);
        $brand = 'Link';
        $suggestions = ['Check out this link!', 'Visit this page', 'Click here'];

        if (str_contains($lowerUrl, 'github')) {
            $brand = 'GitHub';
            $suggestions = [
                'My Open Source Projects 💻',
                'Check out my Repositories',
                'Coding Portfolio'
            ];
        } elseif (str_contains($lowerUrl, 'linkedin')) {
            $brand = 'LinkedIn';
            $suggestions = [
                'My Professional Journey 🤝',
                'Connect with me on LinkedIn',
                'View my Resume & Experience'
            ];
        } elseif (str_contains($lowerUrl, 'twitter') || str_contains($lowerUrl, 'x.com')) {
            $brand = 'X (Twitter)';
            $suggestions = [
                'Join the Conversation on X 🐦',
                'Follow my Tweets',
                'My Thoughts & Updates'
            ];
        } elseif (str_contains($lowerUrl, 'instagram')) {
            $brand = 'Instagram';
            $suggestions = [
                'Follow My Daily Life 📸',
                'Check out my Photos',
                'My Visual Diary'
            ];
        } elseif (str_contains($lowerUrl, 'youtube')) {
            $brand = 'YouTube';
            $suggestions = [
                'Watch My Latest Videos 🎥',
                'Subscribe to my Channel',
                'My Video Content'
            ];
        } elseif (str_contains($lowerUrl, 'spotify')) {
            $brand = 'Spotify';
            $suggestions = [
                'My Ultimate Playlist 🎵',
                'Listen along with me',
                'My Music Rotation'
            ];
        } elseif (str_contains($lowerUrl, 'discord')) {
            $brand = 'Discord';
            $suggestions = [
                'Join My Community 🎮',
                'Chat with us on Discord',
                'My Server Invite'
            ];
        } elseif (str_contains($lowerUrl, 'tiktok')) {
            $brand = 'TikTok';
            $suggestions = [
                'Follow for Daily Content 📱',
                'Watch my TikToks',
                'My Short Videos'
            ];
        } elseif (str_contains($lowerUrl, 'portfolio')) {
            $brand = 'Portfolio';
            $suggestions = [
                'View My Creative Portfolio ✨',
                'My Selected Works',
                'Hire Me!'
            ];
        } elseif (str_contains($lowerUrl, 'blog')) {
            $brand = 'Blog';
            $suggestions = [
                'Read My Latest Articles 📝',
                'My Blog Posts',
                'Thoughts & Insights'
            ];
        } elseif (str_contains($lowerUrl, 'shop') || str_contains($lowerUrl, 'store')) {
            $brand = 'Shop';
            $suggestions = [
                'Shop My Collection 🛍️',
                'Browse Products',
                'My Store'
            ];
        } else {
            $brand = 'Website';
            $suggestions = [
                'Highly Recommended Link 🚀',
                'Visit this Page',
                'Check this out!'
            ];
        }

        return [
            'brand' => $brand,
            'suggestions' => $suggestions,
        ];
    }
}
