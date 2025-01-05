<?php

/*
  |--------------------------------------------------------------------------
  | Web Routes
  |--------------------------------------------------------------------------
  |
  | Here is where you can register web routes for your application. These
  | routes are loaded by the RouteServiceProvider within a group which
  | contains the "web" middleware group. Now create something great!
  |
 */

use App\Folder;
use App\Freelancer;
use App\Post;

Route::get('/', function () {
    \Log::channel('daily_change_status')->debug('Root route');

    // $folder_ids = Folder::where('is_archive',1)->pluck('id');
    // Post::whereIn('folder_id',$folder_ids)->update(['is_archive'=>1]);
    // $freelancer_ids = Post::where('is_archive',0)->pluck('freelance_id');
    // Freelancer::whereIn('id',$freelancer_ids)->update(['has_subscription_content' => 1]);
    // $freelancer_ids = Post::where('is_archive',1)->pluck('freelance_id');
    // Freelancer::whereIn('id',$freelancer_ids)->update(['has_subscription_content' => 0]);
    return view('welcome');
});

Route::get('/has-subscription-content', function () {
    \Log::channel('daily_change_status')->debug('has-subscription-content');

    $folder_ids = Folder::where('is_archive',1)->pluck('id');
    Post::whereIn('folder_id',$folder_ids)->update(['is_archive'=>1]);
    $freelancer_ids = Post::where('is_archive',0)->pluck('freelance_id');
    Freelancer::whereIn('id',$freelancer_ids)->update(['has_subscription_content' => 1]);
    $freelancer_ids = Post::where('is_archive',1)->pluck('freelance_id');
    Freelancer::whereIn('id',$freelancer_ids)->update(['has_subscription_content' => 0]);
    return view('welcome');
});
Route::get('/foo', function () {
    dd(\App\Category::all());
    return view('welcome');
});
Route::get('checkoutWebhook', ['as' => 'checkoutWebhook', 'uses' => 'CheckoutWebhookController@checkoutWebhook']);


Route::group(['middleware' => 'scheduler_auth'], function () {
    Route::post('scheduler', ['as' => 'login', 'uses' => 'SchedulerController@scheduler']);
});

//******************************************* Payment Method ***********************************************

Route::get('paymentSuccess', ['as' => 'paymentSuccess', 'uses' => 'PaymentRequestController@paymentSuccess']);

Route::get('paymentFail', ['as' => 'paymentFail', 'uses' => 'PaymentRequestController@paymentFail']);

Route::get('paymentSuccessForTopUp', ['as' => 'paymentSuccessForTopUp', 'uses' => 'PaymentRequestController@paymentSuccessFotTopUp']);
Route::get('paymentSuccessForPremiumFolder', ['as' => 'paymentSuccessForPremiumFolder', 'uses' => 'PaymentRequestController@paymentSuccessForPremiumFolder']);
Route::get('paymentFailForPremiumFolder', ['as' => 'paymentFailForPremiumFolder', 'uses' => 'PaymentRequestController@paymentFailForPremiumFolder']);

Route::get('paymentFailForTopUp', ['as' => 'paymentFailForTopUp', 'uses' => 'PaymentRequestController@paymentFailForTopUp']);

Route::post('capturePayment', ['as' => 'capturePayment', 'uses' => 'PaymentRequestController@capturePayment']);

Route::get('paymentSuccessForRecurringSubscription', ['as' => 'paymentSuccessForRecurringSubscription', 'uses' => 'PaymentRequestController@paymentSuccessForRecurringSubscription']);

Route::get('paymentFailForRecurringSubscription', ['as' => 'paymentFailForRecurringSubscription', 'uses' => 'PaymentRequestController@paymentFailForRecurringSubscription']);

Route::get('paymentSuccessForSubscription', ['as' => 'paymentSuccessFotSubscription', 'uses' => 'PaymentRequestController@paymentSuccessFotSubscription']);

Route::get('paymentFailForSubscription', ['as' => 'paymentFailForSubscription', 'uses' => 'PaymentRequestController@paymentFailForSubscription']);

//****************************************** End Here ******************************************************

Route::group(['middleware' => 'api_auth'], function () {

    //**************************** Wallet & Payments *****************************************************

    Route::get('subscriptionRenewalAlert', ['as' => 'subscriptionRenewalAlert', 'uses' => 'SubscriptionController@subscriptionRenewalAlert']);

    Route::get('recurringSubscriptionPayment', ['as' => 'recurringSubscriptionPayment', 'uses' => 'SubscriptionController@recurringSubscriptionPayment']);

    Route::post('topUp', ['as' => 'topUp', 'uses' => 'PaymentRequestController@topUp']);

    Route::get('getFreelancerBalance', ['as' => 'getFreelancerBalance', 'uses' => 'PaymentRequestController@getFreelancerBalance']);

    Route::post('updateWallet', ['as' => 'updateWallet', 'uses' => 'PaymentRequestController@updateWallet']);

    Route::post('addCard', ['as' => 'addCard', 'uses' => 'PaymentRequestController@addCustomerCard']);

    Route::get('getUserCards', ['as' => 'getUserCards', 'uses' => 'PaymentRequestController@getCardDetail']);

    Route::delete('deleteUserCards', ['as' => 'deleteUserCards', 'uses' => 'PaymentRequestController@deleteUserCards']);

    Route::get('getToken', ['as' => 'getToken', 'uses' => 'PaymentRequestController@getToken']);

    //*****************************************************************************************************
    // ************************** registration process ****************************************
    Route::post('verifyCode', ['as' => 'verifyCode', 'uses' => 'VerificationController@verifyCode']);
    Route::post('getVerificationCode', ['as' => 'getVerificationCode', 'uses' => 'VerificationController@getVerificationCode']);
    Route::get('getAllProfessions', ['as' => 'getAllProfessions', 'uses' => 'ProfessionController@getAllProfessions']);
    Route::post('updateFreelancer', ['as' => 'updateFreelancer', 'uses' => 'FreelancerController@updateFreelancer']);
    Route::post('updateFreelancerName', ['uses' => 'FreelancerController@updateFreelancerName']);

    Route::get('getWalletBalance', ['uses' => 'CustomerController@customerWalletBalance']);

    Route::post('updateCustomer', ['uses' => 'CustomerController@updateCustomer']); // method changed from post to put
    Route::post('updateCustomerName', ['uses' => 'CustomerController@updateCustomerName']);

    Route::get('getCategories', ['as' => 'getCategories', 'uses' => 'CategoryController@getCategories']);
    Route::post('saveCategory', ['as' => 'saveCategory', 'uses' => 'CategoryController@saveCategory']);
    Route::get('getSubCategories', ['as' => 'getSubCategories', 'uses' => 'SubCategoryController@getSubCategories']);
    Route::post('saveFreelancerCategories', ['as' => 'saveFreelancerCategories', 'uses' => 'CategoryController@saveFreelancerCategories']);
    Route::post('addFreelancerPricing', ['as' => 'addFreelancerPricing', 'uses' => 'PriceController@addFreelancerPricing']);
    Route::get('getFreelancerCalender', ['uses' => 'FreelancerCalenderController@getFreelancerCalender']);
    Route::post('saveFreelancerWeeklySchedule', ['as' => 'saveFreelancerWeeklySchedule', 'uses' => 'ScheduleController@saveFreelancerWeeklySchedule']);
    Route::post('saveFreelancerLocations', ['as' => 'saveFreelancerLocations', 'uses' => 'LocationController@saveFreelancerLocations']);
    Route::get('getPublicProfilePosts', ['uses' => 'PostController@getPublicProfilePosts']);
    Route::post('addInterests', ['as' => 'addInterests', 'uses' => 'CustomerController@addInterests']);
    Route::post('autoLogin', ['as' => 'autoLogin', 'uses' => 'LoginController@autoLogin']);
    Route::get('getCalenderAppointments', ['uses' => 'AppointmentController@getCalenderAppointments']);
//******************************* End Registration *******************************************
//************************* Add Package *********************************
    Route::post('addPackage', ['uses' => 'PackageController@addPackage']);
    Route::post('freelancerAddAppointment', ['uses' => 'AppointmentController@freelancerAddAppointment']);
    Route::get('getPurchasedPackages', ['uses' => 'PackageController@getPurchasedPackages']);
//************************* End Package *********************************
//************************ Book Appointment *****************************
    Route::post('freelancerAddMultipleAppointment', ['uses' => 'AppointmentController@freelancerAddMultipleAppointment']);
//************************ End Here *************************************
//************************* Freelance Dashboard Detail ******************
    Route::get('freelancerGetDashboardDetail', ['uses' => 'FreelancerController@freelancerGetDashboardDetail']);
    Route::get('getDashboardCounts', ['as' => 'getDashboardCounts', 'uses' => 'DashboardController@getDashboardCounts']);
//************************* End Here ************************************
//************************  Add Class And Booking ***********************
    Route::get('getClassDetails', ['uses' => 'ClassController@getClassDetails']);
    Route::post('deleteClass', ['uses' => 'ClassController@deleteClass']);
    Route::post('freelancerAddClass', ['uses' => 'ClassController@freelancerAddClass']);
    Route::post('multipleClassBooking', ['as' => 'multipleClassBooking', 'uses' => 'ClassBookingController@multipleClassBooking']);

    Route::post('classBooking', ['as' => 'classBooking', 'uses' => 'ClassBookingController@classBooking']);

    Route::get('getClassesList', ['uses' => 'ClassController@getClassesList']);
    Route::get('getFreelancerCategories', ['as' => 'getFreelancerCategories', 'uses' => 'CategoryController@getFreelancerCategories']);
    Route::get('getFreelancerActiveCategories', ['as' => 'getFreelancerActiveCategories', 'uses' => 'CategoryController@getFreelancerActiveCategories']);
//************************ End Here ************************************
//*************************** Slots *********************************
    Route::get('getFreelancerAvailableSlots', ['uses' => 'FreelancerController@getFreelancerAvailableSlots']);
//**************************** End Here ********************************
//*************************** UpComing Schedules *********************************
    Route::get('getUpcomingClassSchedules', ['as' => 'getUpcomingClassSchedules', 'uses' => 'ClassController@getUpcomingClassSchedules']);
    Route::post('updateFreelancerWeeklySchedule', ['as' => 'updateFreelancerWeeklySchedule', 'uses' => 'ScheduleController@updateFreelancerWeeklySchedule']);
//**************************** End Here ********************************
//**************************** Logout ********************************
    Route::post('logout', ['as' => 'logout', 'uses' => 'LoginController@logout']);
//*************************** End Here *******************************


    Route::get('getSettings', ['as' => 'getSettings', 'uses' => 'SettingsController@getSettings']);

    Route::get('getFreelancerSubscriptionSettings', ['uses' => 'FreelancerProfileController@getSubscriptionSettings']);

    Route::get('getAllPackages', ['uses' => 'PackageController@getAllPackages']);

    Route::get('getUpcomingAppointments', ['uses' => 'AppointmentController@getUpcomingAppointments']);

    Route::get('freelancerGetAllAppointments', ['uses' => 'AppointmentController@freelancerGetAllAppointments']);

    Route::get('getFreelancerClients', ['uses' => 'FreelancerController@getFreelancerClients']);

    Route::post('searchClient', ['uses' => 'SearchController@searchClient']);

    Route::get('getCustomerFeedPosts', ['as' => 'getCustomerFeedPosts', 'uses' => 'CustomerFeedController@getCustomerFeedPosts']);

    Route::post('sendPromoCodes', ['as' => 'sendPromoCodes', 'uses' => 'PromoCodeController@sendPromoCodes']);

    Route::get('getClientDetails', ['as' => 'getClientDetails', 'uses' => 'ClientController@getClientDetails']);

    Route::post('changeClassStatus', ['uses' => 'ClassController@changeClassStatus']);

    Route::post('addSubscriptionSettings', ['as' => 'addSubscriptionSettings', 'uses' => 'SubscriptionController@addSubscriptionSettings']);

    Route::get('getSubscribers', ['uses' => 'SubscriberController@getSubscribers']);

    Route::get('getFreelancerFollowers', ['uses' => 'FollowerController@getFreelancerFollowers']);

    Route::post('addContent', ['as' => 'addContent', 'uses' => 'PostController@addContent']);

    Route::post('addWalkinCustomer', ['uses' => 'WalkinCustomerController@addWalkinCustomer']);

//    Route::get('getFreelancerProfile', ['uses' => 'FreelancerProfileController@getFreelancerProfile']);
    //******************************************* Customer API ***********************************************

    Route::get('getProfileWithStories', ['as' => 'getProfileWithStories', 'uses' => 'CustomerFeedController@getProfileWithStories']);

    Route::get('getMixedFeedData', ['as' => 'getMixedFeedData', 'uses' => 'CustomerFeedController@getMixedFeedData']);

    Route::get('searchAppointments', ['uses' => 'AppointmentController@searchAppointments']);

    Route::post('addSocialMedia', ['as' => 'addSocialMedia', 'uses' => 'SocialController@addSocialMedia']);

    Route::post('addFolder', ['uses' => 'FolderController@addFolder']);
    Route::post('buyPremiumFolder', ['uses' => 'FolderController@buyPremiumFolder']);

    Route::get('getFolders', ['uses' => 'FolderController@getFolders']);

    Route::get('getProfileSubscription', ['uses' => 'PostController@getProfileSubscription']);

    Route::get('getFolderPosts', ['uses' => 'PostController@getFolderPosts']);

    Route::post('login', ['as' => 'login', 'uses' => 'LoginController@login']);

    Route::get('getCustomerDashboard', ['as' => 'getCustomerDashboard', 'uses' => 'CustomerController@getCustomerDashboard']);

    Route::get('customerGetAllAppointments', ['as' => 'customerGetAllAppointments', 'uses' => 'CustomerAppointmentController@customerGetAllAppointments']);

    Route::get('freelancerGetAppointmentDetail', ['uses' => 'AppointmentController@freelancerGetAppointmentDetail']);

    Route::get('getFreelancerReviews', ['uses' => 'ReviewController@getFreelancerReviews']);

    Route::post('addStoryViews', ['as' => 'addStoryViews', 'uses' => 'StoryController@addStoryViews']);

    Route::get('getNotifications', ['uses' => 'NotificationController@getNotifications']);

    Route::post('searchFreelancers', ['as' => 'searchFreelancers', 'uses' => 'SearchController@searchFreelancers']);

    Route::post('removeStory', ['as' => 'removeStory', 'uses' => 'PostController@removeStory']);

    Route::get('getAvailableClasses', ['as' => 'getAvailableClasses', 'uses' => 'ClassController@getAvailableClasses']);

    Route::get('searchClassSchedule', ['as' => 'searchClassSchedule', 'uses' => 'ClassScheduleController@searchClassSchedule']);

    Route::get('getPurchasedPackageDetails', ['uses' => 'PackageController@getPurchasedPackageDetails']);

    Route::get('getSingleDayClass', ['uses' => 'ClassController@getSingleClassDetail']);

    Route::post('getFavouriteProfilesData', ['as' => 'getFavouriteProfilesData', 'uses' => 'FavouriteController@getFavouriteProfilesData']);

    Route::get('getSavedContent', ['as' => 'getSavedContent', 'uses' => 'BookMarkController@getSavedContent']);

    Route::post('addFeedBack', ['as' => 'addFeedBack', 'uses' => 'FeedBackController@addFeedback']);

// bank detail
    Route::post('updateBankDetail', ['as' => 'updateBankDetail', 'uses' => 'BankController@updateBankDetail']);
    Route::get('getOverviewBankDetail', ['as' => 'getOverviewBankDetail', 'uses' => 'BankController@getOverviewBankDetail']);
    Route::get('getAllTransactions', ['as' => 'getAllTransactions', 'uses' => 'BankController@getAllTransactions']);
    Route::get('getTransactionDetail', ['as' => 'getTransactionDetail', 'uses' => 'BankController@getTransactionDetail']);
    Route::get('getWithdrawRequests', ['as' => 'getWithdrawRequests', 'uses' => 'BankController@getWithdrawRequests']);
    Route::get('getTransactionByType', ['as' => 'getTransactionByType', 'uses' => 'BankController@getTransactionByType']);

    Route::get('payouts', ['as' => 'payouts', 'uses' => 'BankController@getPayouts']);
    Route::get('payoutDetail', ['as' => 'payoutDetail', 'uses' => 'BankController@getPayoutDetail']);

    Route::post('guestLogin', ['as' => 'login', 'uses' => 'LoginController@guestLogin']);

    Route::post('socialLogin', ['as' => 'socialLogin', 'uses' => 'LoginController@socialLogin']);

    Route::post('freelancerSignup', ['as' => 'freelancerSignup', 'uses' => 'RegisterationController@freelancerSignup']);

    Route::post('updateFreelancerLocations', ['as' => 'updateFreelancerLocations', 'uses' => 'LocationController@updateFreelancerLocations']);

    Route::post('addBlockTime', ['uses' => 'FreelancerController@freelancerAddBlockTime']);

    Route::post('addPromoCodes', ['as' => 'addPromoCodes', 'uses' => 'PromoCodeController@addPromoCodes']);
    Route::get('getActivePromoCodes', ['as' => 'getActivePromoCodes', 'uses' => 'PromoCodeController@getActivePromoCodes']);
    Route::get('getExpiredPromoCodes', ['as' => 'getExpiredPromoCodes', 'uses' => 'PromoCodeController@getExpiredPromoCodes']);

    Route::post('validatePromoCodes', ['as' => 'validatePromoCodes', 'uses' => 'PromoCodeController@validatePromoCodes']);
    Route::post('changePassword', ['as' => 'changePassword', 'uses' => 'FreelancerProfileController@changePassword']);
    Route::post('freelancerAddSession', ['as' => 'freelancerAddSession', 'uses' => 'FreelancerController@freelancerAddSession']);

//    Route::get('freelancerGetAppointmentDetail', ['uses' => 'AppointmentController@freelancerGetAppointmentDetail']);

    Route::post('licenceAgreement', ['uses' => 'ActivityController@licenceAgreement']);

    Route::post('freelancerRescheduleAppointment', ['uses' => 'AppointmentController@freelancerRescheduleAppointment']);

    Route::post('updateFreelancerSubscriptionSettings', ['uses' => 'SubscriptionController@updateFreelancerSubscriptionSettings']);
//    Route::get('getFreelancerProfile', ['uses' => 'FreelancerProfileController@getFreelancerProfile']);
    Route::get('getFreelancerSchedule', ['uses' => 'FreelancerScheduleController@getFreelancerSchedule']);

    Route::post('addFreelancerReview', ['uses' => 'ReviewController@addFreelancerReview']);
    Route::post('addReviewReply', ['uses' => 'ReviewController@addReviewReply']);

    Route::post('processFollowing', ['uses' => 'FollowerController@processFollowing']);

    Route::get('getSingleReview', ['uses' => 'ReviewController@getSingleReview']);
    Route::get('getCustomerList', ['uses' => 'CustomerController@getCustomerList']);

    Route::post('customerSignup', ['uses' => 'RegisterationController@customerSignup']);
    Route::post('newCustomerSignup', ['uses' => 'RegisterationController@newCustomerSignup']);
    Route::post('search', ['uses' => 'SearchController@search']);

    // Route::post('changeAppointmentStatus', ['uses' => 'AppointmentController@changeAppointmentStatus']);
    Route::post('changeAppointmentStatus', ['uses' => 'AppointmentController@changeAppointmentsStatus']);

    //Route::get('getSingleDayClass', ['uses' => 'ClassContAroller@getSingleDayClass']);




    Route::get('getCustomerHomeFeed', ['as' => 'getCustomerHomeFeed', 'uses' => 'CustomerController@getCustomerHomeFeed']);

    Route::get('getUserChatSettings', ['as' => 'getUserChatSettings', 'uses' => 'SettingsController@getUserChatSettings']);

    Route::post('updatePackage', ['uses' => 'PackageController@updatePackage']);

    Route::post('updateNotificationSettings', ['uses' => 'SettingsController@updateNotificationSettings']);

    Route::post('addSubscription', ['as' => 'addSubscription', 'uses' => 'SubscriptionController@addSubscription']);
//    Route::post('addPost', ['as' => 'addPost', 'uses' => 'PostController@addPost']);
//    Route::get('getPostDetail', ['uses' => 'PostController@getPostDetail']);

    Route::get('getActiveClassesCount', ['uses' => 'ClassController@getActiveClassesCount']);

    Route::get('getPackageBasicDetails', ['uses' => 'PackageController@getPackageBasicDetails']);
    Route::get('getPackageDetails', ['uses' => 'PackageController@getPackageDetails']);

    Route::post('updateClass', ['as' => 'updateClass', 'uses' => 'ClassController@updateClass']);

    Route::get('getNotificationsBadgeCount', ['uses' => 'NotificationController@getNotificationsBadgeCount']);
    Route::post('updateNotificationStatus', ['as' => 'updateNotificationStatus', 'uses' => 'NotificationController@updateNotificationStatus']);
    Route::post('addPostLike', ['as' => 'addPostLike', 'uses' => 'LikeController@addPostLike']);
    Route::post('addBookmark', ['as' => 'addBookmark', 'uses' => 'BookMarkController@addBookmark']);
    Route::post('shareContent', ['as' => 'shareContent', 'uses' => 'ShareController@shareContent']);

    Route::get('searchChatUsers', ['as' => 'searchChatUsers', 'uses' => 'SearchController@searchChatUsers']);

    Route::get('getProfileStories', ['uses' => 'StoryController@getProfileStories']);
    Route::post('addReportPost', ['as' => 'addReportPost', 'uses' => 'PostController@addReportPost']);
    Route::post('addProfileStories', ['as' => 'addProfileStories', 'uses' => 'StoryController@addProfileStories']);

    Route::post('updatePost', ['as' => 'updatePost', 'uses' => 'PostController@updatePost']);
    Route::post('updateAppointmentDetail', ['as' => 'updateAppointmentDetail', 'uses' => 'AppointmentController@updateAppointmentDetail']);
    Route::post('deletePost', ['as' => 'deletePost', 'uses' => 'PostController@deletePost']);

    Route::get('updateChatSettings', ['as' => 'updateChatSettings', 'uses' => 'SettingsController@updateChatSettings']);

// favorite route
    Route::post('processFavourite', ['as' => 'processFavourite', 'uses' => 'FavouriteController@processFavourite']);
    Route::post('getFavouriteScreenData', ['as' => 'getFavouriteScreenData', 'uses' => 'FavouriteController@getFavouriteScreenData']);

    // creadit card routes
    Route::get('getCreditCards', ['as' => 'getCreditCards', 'uses' => 'CreditCardController@getCreditCards']);
    Route::post('addCreditCards', ['as' => 'addCreditCards', 'uses' => 'CreditCardController@addCreditCards']);
    Route::post('customizeHomeScreen', ['as' => 'customizeHomeScreen', 'uses' => 'HomeController@customizeHomeScreen']);
    Route::get('getPolicy', ['as' => 'getPolicy', 'uses' => 'PolicyController@getPolicy']);

// customer appointments
// class



    Route::get('searchMultipleClassSchedule', ['as' => 'searchMultipleClassSchedule', 'uses' => 'ClassScheduleController@searchMultipleClassSchedule']);

// Customer Home Feed
// payment method routes
    Route::post('prepareCheckout', ['as' => 'prepareCheckout', 'uses' => 'HyperpayController@prepareCheckout']);

    Route::post('updateFolder', ['uses' => 'FolderController@updateFolder']);
    Route::post('deleteFolder', ['uses' => 'FolderController@deleteFolder']);
    Route::get('getAllAppointments', ['as' => 'getAllAppointments', 'uses' => 'CustomerAppointmentController@getAllAppointments']);
    Route::get('getCurrencyRate', ['as' => 'getCurrencyRate', 'uses' => 'BankController@getCurrencyRate']);
    Route::post('deletePromoCode', ['uses' => 'PromoCodeController@deletePromoCode']);

    Route::get('getAnalyticsResult', ['as' => 'getAnalyticsResult', 'uses' => 'AnalyticsController@getAnalyticsResult']);
    Route::get('getFreelancerCategoryDetails', ['as' => 'getFreelancerCategoryDetails', 'uses' => 'CategoryController@getFreelancerCategoryDetails']);
    Route::post('updateFreelancerCategory', ['as' => 'updateFreelancerCategory', 'uses' => 'CategoryController@updateFreelancerCategory']);
    Route::post('forgetPassword', ['as' => 'forgetPassword', 'uses' => 'VerificationController@forgetPassword']);
    Route::post('resetPassword', ['as' => 'resetPassword', 'uses' => 'VerificationController@resetPassword']);
    Route::get('getLikes', ['as' => 'getLikes', 'uses' => 'LikeController@getLikes']);
    Route::post('deletePackage', ['uses' => 'PackageController@deletePackage']);
    Route::post('processSubscription', ['uses' => 'SubscriptionController@processSubscription']);
    Route::post('deleteMedia', ['uses' => 'FreelancerProfileController@deleteMedia']);
    Route::post('hideContent', ['uses' => 'PostController@hideContent']);
    Route::get('getCountries', ['uses' => 'LocationController@getCountries']);

    Route::get('getCurrencyRate', ['as' => 'getCurrencyRate', 'uses' => 'BankController@getCurrencyRate']);

//chat method routes
    Route::post('send-message', ['uses' => 'ChatController@sendChatMessage']);
    Route::post('inbox', ['uses' => 'InboxController@getInboxMessage']);
    Route::post('updateChatStatus', ['uses' => 'InboxController@updateChatStatus']);
    Route::get('getUnreadChatCount', ['uses' => 'InboxController@getUnreadChatCount']);
    Route::get('UpdateAllChatStatus', ['uses' => 'InboxController@UpdateAllChatStatus']);
    Route::post('get-conversation', ['uses' => 'InboxController@chatConversation']);
    Route::post('delete-message', ['uses' => 'DeleteChatController@deleteMessage']);
    Route::post('delete-conversation', ['uses' => 'DeleteChatController@deleteConversation']);

    Route::post('processPaymentRequest', ['as' => 'processPaymentRequest', 'uses' => 'PaymentRequestController@processPaymentRequest']);

    Route::post('createMoyasarForm', ['as' => 'createMoyasarForm', 'uses' => 'MoyasarController@createMoyasarForm']);
    Route::get('getMoyasarForm', ['as' => 'getMoyasarForm', 'uses' => 'MoyasarController@getMoyasarForm']);
    Route::post('updateMoyasarForm', ['as' => 'updateMoyasarForm', 'uses' => 'MoyasarController@updateMoyasarForm']);

    //Pusher Android Push Notifications
    Route::get('/pusher/beams-auth', ['as' => 'PusherAndroidTokenProvider', 'uses' => 'PusherAndroidController@tokenProvider']);
    //Route::get('/pusher/send-notification', ['as' => 'PusherAndroidSendNotification', 'uses' => 'PusherAndroidController@sendTestNotification']);
});

// callback for chat
Route::post('extension-callback', ['uses' => 'CallBackController@extensionCallback']);

//Test Routes
Route::post('uploadTestFile', ['as' => 'uploadTestFile', 'uses' => 'ActivityController@uploadTestFile']);
Route::post('createAdminCustomer', ['uses' => 'RegisterationController@createAdminCustomer']);
Route::post('testExchangeRate', ['uses' => 'AppointmentController@testExchangeRate']);

// Called through Universal Links

Route::get('getPostDetail', ['uses' => 'PostController@getPostDetail']);
Route::get('getPost', ['uses' => 'PostController@getPost']);

////////////// Web pages ////////////
Route::get('policy', ['as' => 'policy', 'uses' => 'WebController@policyPage']);
Route::get('terms', ['as' => 'terms', 'uses' => 'WebController@termsPage']);
Route::get('install-app', ['as' => 'install-app', 'uses' => 'PagesController@installApp']);

//public method routes for chat authorization
Route::post('authorize', ['uses' => 'ChatController@authorizePusher']);
Route::post('authorize-chat-window', ['uses' => 'ChatController@authorizeChatWindow']);
// This end-ppoint is only use for pushed wen hook as public
Route::post('handle-presence', ['uses' => 'WebHookController@handlePresence']);
Route::get('channel-data', ['uses' => 'WebHookController@checkChannelData']);

// split order notification hook
Route::post('splitOrderNotificationHook', ['as' => 'splitOrderNotificationHook', 'uses' => 'PaymentRequestController@splitOrderNotificationHook']);

Route::post('moyasar/payment/webhook', ['as' => 'MoyasarPaymentWebHook', 'uses' => 'MoyasarController@MoyasarPaymentWebHook']);

Route::get('moyasar/invoice/callback', ['uses' => 'MoyasarController@invoiceCallback']);
Route::get('moyasar/payment/callback', ['as' => 'MoyasarPaymentCallback', 'uses' => 'MoyasarController@paymentCallback']);
Route::get('moyasar/form/{id}', ['as' => 'MoyasarWebForm', 'uses' => 'MoyasarController@form']);
Route::get('moyasar/form/{id}/failed', ['as' => 'MoyasarWebFormFailed', 'uses' => 'MoyasarController@paymentFailedCallback']);

//AWS SES endpoints


Route::post('getsesbounce', ['as' => 'getsesbounce', 'uses' => 'SESController@logBounce']);
Route::post('getsescomplaints', ['as' => 'getsescomplaints', 'uses' => 'SESController@logComplaints']);

// Deep link urls
Route::get('getFreelancerProfile', ['uses' => 'FreelancerProfileController@getFreelancerProfile']);

// get system settings
Route::get('getSystemSettings', ['uses' => 'HomeController@getSystemSettings']);

Route::get('deleteUser', ['uses' => 'HomeController@deleteUser']);
Route::get('testPushNotification', ['uses' => 'HomeController@testPushNotification']);
Route::get('runSchedulerv1', ['uses' => 'HomeController@runScheduler']);
Route::post('runSchedulerv1', ['uses' => 'HomeController@runScheduler']);
