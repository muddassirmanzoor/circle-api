<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class BookMark extends Model {

    // use Notifiable;

    use \BinaryCabin\LaravelUUID\Traits\HasUUID;

    protected $table = 'book_marks';
    protected $primaryKey = 'id';
    protected $uuidFieldName = 'book_mark_uuid';
    public $timestamps = true;
    protected $fillable = [
        'book_mark_uuid',
        'customer_id',
        'post_id',
        'is_archive'
    ];

    public function post(){
        return $this->hasOne(Post::class, 'id', 'post_id');
    }

    public function customer(){
        return $this->hasOne(Customer::class, 'id', 'customer_id');
    }

    public static function addBookMark($inputs) {
        $result = [];
        if (BookMark::where('customer_id', '=', $inputs['customer_id'])->where('post_id', '=', $inputs['post_id'])->exists()) {
          return   $result['already_exist'] = true;
        } else {
            $result = BookMark::create($inputs);
            return !empty($result) ? $result->toArray() : [];
        }
    }

    public static function getBookMarkedPostIds($column, $value, $pluck_field = 'post_id') {
        $result = BookMark::where($column, '=', $value)->where('is_archive', 0)->with(['post', 'customer'])->get(); //->pluck($pluck_field);
        
        $result = !empty($result) ? $result->toArray() : [];

        $pluck_values = [];
        foreach ($result as $bookmark):
            
            if((!empty($bookmark['post']) && $bookmark['post']['post_type'] == 'unpaid')
                || empty($bookmark['customer'])): // Skip sub check if bookmark customer_uuid is not customer user or if post is unpaid
                $pluck_values[] = $bookmark[$pluck_field];
                continue;
            endif;

            $is_subscribed = Subscription::checkSubscriber($bookmark['customer_id'], $bookmark['post']['freelance_id']);

            if ($is_subscribed):
                $pluck_values[] = $bookmark[$pluck_field];
            else:
                static::deleteParticularBookMark($bookmark['customer_id'], $bookmark['post_id']);
            endif;
        endforeach;

        return $pluck_values;
    }

    public static function deleteParticularBookMark($customer_id, $post_id) {
        if (BookMark::where('customer_id', '=', $customer_id)->where('post_id', '=', $post_id)->exists()) {
            return BookMark::where('customer_id', '=', $customer_id)->where('post_id', '=', $post_id)->delete();
        }
        return true;
    }

}
