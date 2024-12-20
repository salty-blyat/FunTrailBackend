<?php

namespace App\Http\Controllers\Comment;

use App\Http\Controllers\Controller;
use App\Models\Hotel;
use App\Models\HotelComment;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class HotelCommentController extends Controller
{
    /**
     * Display a listing of the hotel comments.
     *
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        // Define the number of comments to load per page
        $perPage = 5; // Adjust this value as needed

        // Load comments with replies, order by most recent, and apply pagination
        $comments = HotelComment::with('replies')
            ->where('parent_id', null)
            ->orderBy('created_at', 'desc') // Order by most recent
            ->paginate($perPage);

        // Format the created_at timestamp for each comment
        $comments->getCollection()->transform(function ($comment) {
            $comment->created_at = $comment->created_at->diffForHumans();
            return $comment;
        });

        // Apply any pagination cleaning or customization if needed
        $comments = cleanPagination($comments);

        return $this->successResponse($comments);
    }


    /**
     * Store a newly created hotel comment in storage.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {

        $user_id = Auth::id();

        $validator = Validator::make($request->all(), [
            'context' => 'required|string|max:255',
            'star' => 'nullable|integer|min:1|max:5',
            'hotel_id' => 'required|exists:hotels,id',
            'parent_id' => 'nullable|exists:hotel_comments,id', // Allow replies to reference existing comments
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->first(), 422);
        }

        $comment = HotelComment::create([
            'context'       => $request->context,
            'star'          => $request->star,
            'hotel_id'      => $request->hotel_id,
            'parent_id'     => $request->parent_id,
            'user_id'       => $user_id,
        ]);

        return $this->successResponse($comment, "Comment created successfully", 201);
    }

    /**
     * Display the specified hotel comment.
     *
     * @param int $hotel_id
     * @return JsonResponse
     */
    public function show(Request $request,int $hotel_id): JsonResponse
    {
        $perPage = $request->query('per_page', 5);
        // Retrieve comments for the specified hotel, including their replies
        $comments = HotelComment::where('hotel_id', $hotel_id)
            ->orderBy('created_at', 'desc') // Order by most recent
            ->where('parent_id', null) // To only get top-level comments
            ->with(['user:id,username,profile_img', 'replies' => function ($query) {
                $query->with('user:id,username,profile_img'); // Load replies with their respective users
            }])
            ->paginate($perPage);


        if ($comments->isEmpty()) {
            return $this->errorResponse("No comments found for this hotel", 404);
        }

        // Format the created_at timestamp for each comment and move user fields to the same level
        $comments->transform(function ($comment) {
            // Create a new variable for the formatted created_at
            $formattedCreatedAt = $comment->created_at->diffForHumans([
                'parts' => 1,
                'join' => ', ',
                'short' => true,
            ]);

            // Add the formatted timestamp to the comment object under a new key
            $comment->commented_at = $formattedCreatedAt;

            // Include the username and profile_img from the related user model
            $comment->username = $comment->user->username ?? 'Anonymous'; // Add fallback if user is null
            $comment->profile_img = $comment->user->profile_img; // Include profile_img if available

            // Unset the 'user' object to keep the response clean
            unset($comment->user);

            // Process replies
            if ($comment->replies) {
                $comment->replies->transform(function ($reply) {
                    $reply->commented_at = $reply->created_at->diffForHumans(); // Format the timestamp for replies
                    $reply->username = $reply->user->username ?? 'Anonymous'; // Add fallback if user is null
                    $reply->profile_img = $reply->user->profile_img; // Include profile_img if available
                    unset($reply->user); // Clean up the response
                    return $reply;
                });
            }

            return $comment; // Return the modified comment
        });

        $comments = cleanPagination($comments);

        return $this->successResponse($comments, "Comments retrieved successfully");
    }



    /**
     * Update the specified hotel comment in storage.
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(Request $request): JsonResponse
    {
        $id = $request->input('id');
        $context = $request->input('context');
        $comment = HotelComment::find($id);
        $user = Auth::user();


        if (!$comment) {
            return $this->errorResponse("Comment not found", 404);
        }

        // Check if the authenticated user is the hotel owner or the comment author

        $isCommenter = $user->id === $comment->user_id;

        // If neither the hotel owner nor the commenter, return unauthorized
        if (!$isCommenter) {
            return $this->errorResponse("Unauthorized", 403);
        }


        $validator = Validator::make($request->all(), [
            'context' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->first(), 422);
        }

        // Update only the 'context' field
        $comment->context = $context;
        $comment->save(); // Save the changes

        return $this->successResponse("Comment updated successfully");
    }

    /**
     * Remove the specified hotel comment from storage.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(Request $request): JsonResponse
    {
        // Fetch the authenticated user
        $user = Auth::user();

        // Retrieve the comment ID from the request's form data
        $id = $request->input('id');

        // Find the comment
        $comment = HotelComment::find($id);

        if (!$comment) {
            return $this->errorResponse("Comment not found", 404);
        }

        // Find the hotel owned by the user
        $hotel = Hotel::where('id', $comment->hotel_id)->first();

        // Check if the authenticated user is the hotel owner or the comment author
        $isHotelOwner = $hotel ? $hotel->user_id === $user->id : false;
        $isCommenter = $user->id === $comment->user_id;

        // If neither the hotel owner nor the commenter, return unauthorized
        if (!$isHotelOwner && !$isCommenter) {
            return $this->errorResponse("Unauthorized", 403);
        }

        // Delete all replies (child comments) where parent_id is the comment id
        HotelComment::where('parent_id', $id)->delete();

        // Delete the parent comment
        $comment->delete();

        return $this->successResponse("Comment and its replies deleted successfully");
    }
}
