<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Services\GenerationApplicationService;
use App\Domain\Generation\DTOs\CreateGenerationData;
use App\Domain\Generation\Enums\ImageQuality;
use App\Domain\Generation\Enums\ImageSize;
use App\Domain\Generation\Exceptions\InsufficientCreditsException;
use App\Domain\Generation\Models\Generation;
use App\Http\Requests\CreateGenerationRequest;
use App\Http\Resources\GenerationResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class GenerationController extends Controller
{
    public function __construct(
        private readonly GenerationApplicationService $appService,
    ) {}

    public function index(Request $request): Response
    {
        $generations = Generation::where('user_id', $request->user()->id)
            ->latest()
            ->paginate(12);

        return Inertia::render('Generations/Index', [
            'generations' => GenerationResource::collection($generations),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Generations/Create', [
            'sizes'     => collect(ImageSize::cases())->map(fn (ImageSize $s) => [
                'value' => $s->value,
                'label' => $s->label(),
            ]),
            'qualities' => collect(ImageQuality::cases())->map(fn (ImageQuality $q) => [
                'value' => $q->value,
                'label' => $q->label(),
            ]),
        ]);
    }

    public function store(CreateGenerationRequest $request): \Illuminate\Http\RedirectResponse
    {
        $data = new CreateGenerationData(
            userId:          $request->user()->id,
            prompt:          $request->string('prompt')->toString(),
            size:            ImageSize::from($request->input('size')),
            quality:         ImageQuality::from($request->input('quality')),
            referenceImages: $request->input('reference_images', []),
        );

        try {
            $generation = $this->appService->initiateGeneration($data);
        } catch (InsufficientCreditsException $e) {
            return redirect()
                ->route('generations.create')
                ->with('error', $e->getMessage());
        }

        return redirect()
            ->route('generations.show', $generation)
            ->with('success', 'Generation queued! We\'ll notify you when it\'s ready.');
    }

    public function show(Request $request, Generation $generation): Response
    {
        $this->authorize('view', $generation);

        $generation->load('webhookEvents');

        return Inertia::render('Generations/Show', [
            'generation'    => (new GenerationResource($generation))->resolve(),
            'webhookEvents' => $generation->webhookEvents->map(fn ($e) => [
                'event_type'  => $e->event_type,
                'processed_at'=> $e->processed_at?->toIso8601String(),
                'created_at'  => $e->created_at->toIso8601String(),
            ]),
        ]);
    }

    public function estimateCost(Request $request): JsonResponse
    {
        $request->validate([
            'size'    => ['required', 'string'],
            'quality' => ['required', 'string'],
        ]);

        try {
            $size    = ImageSize::from($request->input('size'));
            $quality = ImageQuality::from($request->input('quality'));
        } catch (\ValueError) {
            return response()->json(['error' => 'Invalid size or quality.'], 422);
        }

        $estimate = $this->appService->estimateCost(
            $size,
            $quality,
            $request->input('prompt', ''),
            (int) $request->input('reference_image_count', 0),
        );

        return response()->json($estimate);
    }
}
