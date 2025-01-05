<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Review extends Model {

    use \BinaryCabin\LaravelUUID\Traits\HasUUID;

    protected $table = 'reviews';
    protected $primaryKey = 'id';
    protected $uuidFieldName = 'review_uuid';
    public $timestamps = true;
    protected $fillable = [
        'review_uuid',
        'reviewer_id',
        'reviewed_id',
        'content_id',
        'rating',
        'review',
        'type',
        'is_review',
        'is_archive'
    ];

    public function customer() {
        return $this->hasOne('\App\Customer', 'id', 'reviewer_id');
    }

    public function appointment() {
        return $this->hasOne('\App\Appointment', 'id', 'content_id');
    }

    public function reply() {
        return $this->hasOne('\App\ReviewReply', 'review_id', 'id');
    }

    protected function saveFreelancerReview($data) {
        $result = Review::create($data);
        return ($result) ? $result->toArray() : [];
    }

    protected function getFreelancerReviews($column, $value, $search_params = [], $limit = null, $offset = null) {
        $query = Review::where($column, '=', $value)
                ->with('customer')
                ->with('reply.customer')
                ->with('reply.freelancer');
        if (isset($search_params['type']) && !empty($search_params['type'])) {
            $query->where('type', $search_params['type']);
        }
        if (!empty($offset)) {
            $query = $query->offset($offset);
        }
        if (!empty($limit)) {
            $query = $query->limit($limit);
        }
        $query->where('is_review', 1);
        $query = $query->orderBy('created_at', 'DESC');
        $result = $query->get();
        return !empty($result) ? $result->toArray() : [];
    }

    protected function getReviewsCount($column, $value) {
        return Review::where($column, '=', $value)->where('review', '!=', '')->count();
    }

    protected function getReviewsAvg($column, $value) {
        return Review::where($column, '=', $value)->where('is_review', 1)->avg('rating') ?? "";
    }

    protected function checkCustomerReview($column, $value, $inputs = []) {
        $result = Review::where($column, $value);
        if (!empty($inputs) && $inputs['content_id'])
            $result->where('content_id', $inputs['content_id']);
        $record = $result->first();
        return !empty($record) ? $record->toArray() : [];
    }

    protected function getReviews($column, $value) {
        $result = Review::where($column, '=', $value)->where('rating', '!=', 0)->get();
        return !empty($result) ? $result->toArray() : [];
    }

    protected function getSingleReview($column, $value) {
        $query = Review::where($column, '=', $value)
                ->with('customer')
                ->with('appointment')
                ->with('reply.customer')
                ->with('reply.freelancer');
        $result = $query->first();
        return !empty($result) ? $result->toArray() : [];
    }

}
