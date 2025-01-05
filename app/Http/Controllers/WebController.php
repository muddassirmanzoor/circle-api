<?php

namespace App\Http\Controllers;

class WebController extends Controller {

    public function policyPage() {
        try {
            return view('policy');
        } catch (\Illuminate\Database\QueryException $ex) {
            return $ex->getMessage();
        } catch (\Exception $ex) {
            return $ex->getMessage();
        }
    }

    public function termsPage() {
        try {
            return view('terms');
        } catch (\Illuminate\Database\QueryException $ex) {
            return $ex->getMessage();
        } catch (\Exception $ex) {
            return $ex->getMessage();
        }
    }

}
