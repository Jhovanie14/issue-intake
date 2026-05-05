<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreIssueRequest;
use App\Http\Requests\UpdateIssueRequest;
use App\Http\Resources\IssueResource;
use App\Models\Issue;
use App\Services\IssueService;
use Illuminate\Http\Request;

class IssueController extends Controller
{

    public function __construct(private IssueService $issueService,) {}
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Issue::query();

        $query->when($request->status, fn($q, $v) => $q->where('status', $v));
        $query->when($request->category, fn($q, $v) => $q->where('category', $v));
        $query->when($request->priority, fn($q, $v) => $q->where('priority', $v));

        return IssueResource::collection($query->latest()->paginate(20));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreIssueRequest $request)
    {
       $issue = $this->issueService->create($request->validated());

        return new IssueResource($issue);
    }

    /**
     * Display the specified resource.
     */
    public function show(Issue $issue)
    {
        return new IssueResource($issue);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateIssueRequest $request, Issue $issue)
    {
        $issue = $this->issueService->update($issue, $request->validated());

        return new IssueResource($issue);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Issue $issue)
    {
        $issue->delete();

        return response()->noContent();
    }
}
