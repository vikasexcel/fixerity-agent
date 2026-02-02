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

use App\Http\Controllers\AdminController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\OrderWiseChatController;
use App\Http\Controllers\OtherServiceController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\OtherServiceAdminController;
use App\Http\Controllers\Auth\AuthPagesController;
use App\Http\Controllers\AccountAdminController;
use App\Http\Controllers\ReportIssueController;
use Illuminate\Support\Facades\Auth;

Route::get('/', function () {
    // return view('homepage');
    return view('welcome');
})->name('front');


//paymongo routes

//web hook success
Route::get('mongo-pay-success/{user_id}/{order_id}', [PaymentController::class, 'getMongoPaySuccess'])->name('mongo_pay.success');
//web hook failed
Route::get('mongo-pay-failed/{user_id}/{order_id}', [PaymentController::class, 'getMongoPayFailed'])->name('mongo_pay.failed');

Route::get('paypmongo-success/', [PaymentController::class, 'getPaypmongoSuccess'])->name('paypmongo.success');
Route::get('paypmongo-failed', [PaymentController::class, 'getPaypmongoFailed'])->name('paypmongo.failed');
Route::get('mongo-pay-error', [PaymentController::class, 'getMongoPayError'])->name('mongo_pay.error');
//end paymongo routes

//paypal route's
Route::get('paypal', [PaymentController::class, 'getPaymentStatus'])->name('payment.status');

Route::get('paypal-success', [PaymentController::class, 'getPaypalSuccess'])->name('paypal.success');
Route::get('paypal-failed', [PaymentController::class, 'getPaypalFailed'])->name('paypal.failed');
//end paypal routes

//support pages routes
Route::get('/terms-and-conditions', [HomeController::class, 'getTermsAndConditions'])->name('get:terms-and-conditions');
Route::get('/disclaimer', [HomeController::class, 'getDisclaimer'])->name('get:disclaimer');
Route::get('/privacy-policy', [HomeController::class, 'getPrivacyPolicy'])->name('get:privacy-policy');
Route::get('/faq', [HomeController::class, 'getFaq'])->name('get:faq');
Route::get('/security', [HomeController::class, 'getSecurity'])->name('get:security');
//Route::get('/contact-us', [HomeController::class, 'getContactUs'])->name('get:contact-us');

Route::get('/provider/terms-and-conditions', [HomeController::class, 'getProviderTermsAndConditions'])->name('get:provider:terms-and-conditions');

Route::get('/provider/privacy-policy', [HomeController::class, 'getProviderPrivacyPolicy'])->name('get:provider:privacy-policy');
//end support pages routes

Route::get('/deletion/{reference}', [HomeController::class, 'postDataDeletionStatus'])->name('get:deletion_status');
Route::get('/provider-documents/{filename}', [HomeController::class, 'getfile'])->name('get.file');


/* -----------------------------------For Play-Store, App-Store Upload account_deletion Module------------------------------ */
Route::get('/account-deletion/login', [HomeController::class, 'getAccountDeletion'])->name('get:account:deletion:login');
Route::post('/account-deletion/login', [HomeController::class, 'postAccountDeletion'])->name('post:account:deletion:login');

//Route::get('/account-deletion/verification', [HomeController::class, 'getAccountDeletionVerification'])->name('get:account:deletion:verification');
//Route::post('/account-deletion/verification', [HomeController::class, 'postAccountDeletionVerification'])->name('post:account:deletion:verification');

//Route::get('/account-deletion/resend-verification-code', [HomeController::class, 'getAccountDeletionRensendVerificationCode'])->name('get:account:deletion:resend-verification-code');

Route::get('/account-deletion/profile', [HomeController::class, 'getAccountDeletionProfile'])->name('get:account:deletion:profile');

Route::get('/account-deletion/logout/{guard}', [HomeController::class, 'getAccountDeletionLogout'])->name('get:account:deletion:logout');

// Delete Account
Route::post('/account-deletion/delete-account', [HomeController::class, 'postAccountDeletionDeleteAccount'])->name('post:account:deletion:delete-account:logout');

/// Social Login
Route::get('{guards}/auth/{provider}', [HomeController::class, 'redirectToGoogle'])->name('get:social_auth');
Route::get('auth/{provider}/callback', [HomeController::class, 'handleGoogleCallback'])->name('get:auth_callback');

/* -----------------------------------End For Play-Store, App-Store Upload account_deletion Module-------------------------- */

//super admin
Route::group(['middleware' => 'revalidate'], function () {
    Route::get('/admin/login', [AuthPagesController::class, 'getAdminLogin'])->name('get:admin:login');
    Route::post('/admin/login', [LoginController::class, 'postSuperAdminLogin'])->name('post:admin:update_super_admin_login');
});
Route::prefix('admin')->group(function () {
    Route::group(['middleware' => 'auth:admin'], function () {
        Route::get('/logout/{admin}', [LoginController::class, 'logout'])->name('admin:logout');
        Route::group(['middleware' => 'revalidate'], function () {
            Route::group(['middleware' => 'adminrole'], function () {
                //test-mail
                Route::get('/test-mail', [AdminController::class, 'getAdminTest_Mail'])->name('get:admin:test_mail');
                Route::get('/test-mail', [AdminController::class, 'getAdminTest_Mail'])->name('get:admin:test_mail');

                Route::get('/change-password', [ResetPasswordController::class, 'getAdminChangePassword'])->name('get:admin:change_password');
                Route::post('/change-password', [ResetPasswordController::class, 'postAdminChangePassword'])->name('post:admin:change_password');
                Route::get('/dashboard', [AdminController::class, 'getAdminDashboard'])->name('get:admin:dashboard');

                //world cities list
                Route::get('/world-country-list', [AdminController::class, 'getAdminWorldCountryList'])->name('get:admin:world_country_list');
                Route::get('/add-country', [AdminController::class, 'getAdminAddCountry'])->name('get:admin:add_country');
                //Route::get('/country-city-list/{slug}', [AdminController::class, 'getAdminCountryCityList'])->name('get:admin:country_city_list');
                //Route::get('/add-city/{slug}', [AdminController::class, 'getAdminAddCity'])->name('get:admin:add_city');
                //Route::post('/update-country-city', [AdminController::class, 'postUpdateCountryCity'])->name('post:admin:update_country_city');
                Route::post('/update-country', [AdminController::class, 'postUpdateCountry'])->name('post:admin:update_country');

                Route::get('/world-currency-list', [AdminController::class, 'getAdminWorldCurrencyList'])->name('get:admin:world_currency_list');
                Route::post('/world-currency-list', [AdminController::class, 'postAdminWorldCurrencyList'])->name('post:admin:world_currency_list');

                //service category
                Route::get('/service-category-list', [AdminController::class, 'getServiceCategoryList'])->name('get:admin:service_category_list');
                Route::get('/service-category-change-status', [AdminController::class, 'getServiceCategoryChangeStatus'])->name('get:admin:service_category_change_status');

                Route::get('/add-service-category', [AdminController::class, 'getAddServiceCategory'])->name('get:admin:add_service_category');
                Route::get('/edit-service-category/{slug}', [AdminController::class, 'getEditServiceCategory'])->name('get:admin:edit_service_category');
                Route::get('/delete-service-category', [AdminController::class, 'getDeleteServiceCategory'])->name('get:admin:delete_service_category');
                Route::post('/update-service-category', [AdminController::class, 'postUpdateServiceCategory'])->name('post:admin:update_service_category');

                Route::get('/edit-all-service-category/{slug}', [AdminController::class, 'getEditServiceCategory'])->name('get:admin:edit_all_service_category');
                Route::post('/update-all-service-category', [AdminController::class, 'postUpdateServiceCategory'])->name('post:admin:update_all_service_category');

                //user
                Route::get('/customer-list', [AdminController::class, 'getAdminUserList'])->name('get:admin:user_list');
                Route::get('/add-customer', [AdminController::class, 'getAdminAddUser'])->name('get:admin:add_user');
                Route::get('/edit-customer/{slug}', [AdminController::class, 'getAdminEditUser'])->name('get:admin:edit_user');
                Route::get('/delete-customer', [AdminController::class, 'getAdminDeleteUser'])->name('get:admin:delete_user');
                Route::post('/update-customer', [AdminController::class, 'postAdminUpdateUser'])->name('post:admin:update_user');
                Route::get('/customer-order-list/{id}', [AdminController::class, 'postAdminCustomerOrderList'])->name('post:admin:customer_order_list');
                Route::get('/customer-wallet-transaction/{id}', [AdminController::class, 'postAdminCustomerWalletTransaction'])->name('post:admin:customer_wallet_transaction');
                Route::get('/update-customer-wallet-transaction', [AdminController::class, 'postAdminUpdateCustomerWalletTransaction'])->name('get:admin:update_customer_wallet_transaction');
                //ajax user status update
                Route::get('/update-user-status', [AdminController::class, 'getAdminUpdateUserStatus'])->name('get:ajax:admin:update_user_status');
                Route::get('/user-list-new', [AdminController::class, 'getAdminUserListNew'])->name('get:admin:user_list_new');

                //user review list
                Route::get('/customer-review-list/{user_id?}', [AdminController::class, 'getAdminUserReviewList'])->name('get:admin:user_review_list');
                Route::get('/update-user-review-status', [AdminController::class, 'getAdminUpdateUserReviewStatus'])->name('get:ajax:admin:update_user_review_status');
                Route::get('/delete-customer-review', [AdminController::class, 'getAdminDeleteUserReview'])->name('get:admin:delete_user_review');

                //Cash-out Module
                Route::get('/cash-out', [OtherServiceController::class, 'getOtherServiceCashOutList'])->name('get:admin:provider_cash_out_list');
                Route::get('/cash-out-new', [OtherServiceController::class, 'getOtherServiceCashOutListNew'])->name('get:admin:provider_cash_out_list_new');
                Route::get('/update-cash-out-status', [OtherServiceController::class, 'getUpdateOtherServiceCashOutStatus'])->name('get:admin:provider_update_cash_out_status');

                //ajax required- documents status update
                Route::get('/update-required-documents-status', [AdminController::class, 'getAjaxUpdateAdminRequiredDocumentStatus'])->name('get:ajax:admin:update_required_document_status');
                //ajax provider approved reject document
                Route::get('/approved-reject-provider-document', [AdminController::class, 'getAjaxUpdateAdminApprovedRejectProviderDocument'])->name('get:ajax:admin:update_approved_reject_provider_document');

                //get general settings
                Route::get('/site-setting', [AdminController::class, 'getAdminGeneralSetting'])->name('get:admin:general_setting');
                Route::post('/site-setting', [AdminController::class, 'postAdminUpdateGeneralSetting'])->name('post:admin:update_general_setting');

                //get general settings
                Route::get('/app-version-setting', [AdminController::class, 'getAdminAppVersionSetting'])->name('get:admin:app_version_setting');
                Route::post('/app-version-setting', [AdminController::class, 'postAdminUpdateAppVersionSetting'])->name('post:admin:update_app_version_setting');

                //get push notification
                Route::get('/push-notification', [AdminController::class, 'getAdminPushNotification'])->name('get:admin:push_notification');
                Route::post('/push-notification', [AdminController::class, 'postAdminUpdatePushNotification'])->name('post:admin:update_push_notification');
                Route::get('/remove-push-notification', [AdminController::class, 'getAdminRemovePushNotification'])->name('get:admin:remove_push_notification');

                //provider
                Route::get('/{slug}/provider-list', [AdminController::class, 'getAdminProviderList'])->name('get:admin:provider_list');
                //Route::get('/{slug}/add-provider-services', [AdminController::class, 'getAdminAddProviderServices'])->name('get:admin:add_provider_services');
                Route::get('/{slug}/add-provider-services/{provider_id}', [AdminController::class, 'getAdminAddProviderServices'])->name('get:admin:add_provider_services');

                //un-approved (Pending) provider list
                Route::get('/pending-provider-list', [AdminController::class, 'getAdminPendingProviderList'])->name('get:admin:pending_provider_list');
                Route::get('/provider-services/pending-provider-list', [AdminController::class, 'getAdminPendingProviderList'])->name('get:admin:pending_other_provider_list');

                //pages route
                Route::get('/support-page-list', [AdminController::class, 'getAdminSupportPages'])->name('get:admin:support_pages');
                Route::get('/add-support-page', [AdminController::class, 'getAdminAddPages'])->name('get:admin:add_pages');
                Route::get('/edit-support-page/{page_id}', [AdminController::class, 'getAdminEditPages'])->name('get:admin:edit_pages');
                Route::post('/update-support-page', [AdminController::class, 'postAdminUpdateSupportPages'])->name('post:admin:update_pages');
                Route::get('/delete-support-page', [AdminController::class, 'getAdminDeleteSupportPages'])->name('get:admin:delete_support_page');

                Route::get('/about-us', [AdminController::class, 'getAdminAboutPages'])->name('get:admin:about-us');
                Route::get('/contact-us', [AdminController::class, 'getAdminContactUsPages'])->name('get:admin:contact-us');
                Route::get('/faq', [AdminController::class, 'getAdminFaqPages'])->name('get:admin:faq');

                Route::post('/update', [AdminController::class, 'postAdminUpdateSupportPages'])->name('post:admin:update_pages');

                Route::get('/un-register-provider-list', [AdminController::class, 'getAdminUnRegisterProviderList'])->name('get:admin:un_register_provider_list');
                Route::get('/delete-un-register-provider', [AdminController::class, 'getDeleteUnRegisterProvider'])->name('get:admin:delete_un_register_provider');
                //on-demand service list
                //Route::get('/on-demand/service-list', [OtherServiceController::class,'getOtherServiceList'])->name('get:admin:other_service_list');
                //sub admin route
                Route::get('/sub-admin-list', [AdminController::class, 'getAdminSubAdminList'])->name('get:admin:sub_admin_list');
                Route::get('/add-sub-admin', [AdminController::class, 'getAdminAddSubAdmin'])->name('get:admin:add_sub_admin');
                Route::get('/edit-sub-admin/{admin_id}', [AdminController::class, 'getAdminEditSubAdmin'])->name('get:admin:edit_sub_admin');
                Route::post('/update-sub-admin', [AdminController::class, 'postAdminUpdateSubAdmin'])->name('post:admin:update_sub_admin');
                Route::get('/delete-sub-admin', [AdminController::class, 'getAdminDeleteSubAdmin'])->name('get:admin:delete_sub_admin');

                //Home page banner
                Route::get('/home-page-banner-list', [AdminController::class, 'getHomePageBannerList'])->name('get:admin:home_page_banner_list');
                Route::get('/home-page-banner', [AdminController::class, 'getHomePageBanner'])->name('get:admin:home_page_banner');
                Route::get('/edit-page-banner/{id}', [AdminController::class, 'getHomePageBanner'])->name('get:admin:edit_home_page_banner');
                Route::post('/add-home-page-banner', [AdminController::class, 'AddEditHomePageBanner'])->name('post:admin:add_home_page_banner');
                Route::get('/home-banner-change-status', [AdminController::class, 'getAdminHomeBannerChangeStatus'])->name('get:admin:change_home_banner_status');
                Route::get('/delete-home-banner', [AdminController::class, 'getAdminDeleteHomeBanner'])->name('get:admin:delete_home_banner');

                //Home page slider
                Route::get('/home-page-slider-list', [AdminController::class, 'getHomePageSliderList'])->name('get:admin:home_page_slider_list');
                Route::get('/home-page-slider', [AdminController::class, 'getHomePageSlider'])->name('get:admin:home_page_slider');
                Route::get('/edit-page-slider/{id}', [AdminController::class, 'getHomePageSlider'])->name('get:admin:edit_home_page_slider');
                Route::post('/add-home-page-slider', [AdminController::class, 'AddEditHomePageSlider'])->name('post:admin:add_home_page_slider');
                Route::get('/home-slider-change-status', [AdminController::class, 'getAdminHomeSliderChangeStatus'])->name('get:admin:change_home_slider_status');
                Route::get('/delete-home-slider', [AdminController::class, 'getAdminDeleteHomeSlider'])->name('get:admin:delete_home_slider');

                //set service category seq
                Route::get('/ordering-service-category-list', [AdminController::class, 'getOrderServiceCategoryList'])->name('get:admin:ordering_service_category_list');
                Route::get('/ordering-service-category-status', [AdminController::class, 'getStoreCategoryChangeStatus'])->name('get:admin:ordering_service_category_list_status');
                Route::post('/ordering-service-category-sorting', [AdminController::class, 'postOrderingServiceCategorySorting'])->name('post:admin:ordering_service_category_sorting');

                //Home page Spot Light Section
                Route::get('/home-page-spot-light-list', [AdminController::class, 'getHomePageSpotLightList'])->name('get:admin:home_page_spot_light_list');
                Route::get('/add-home-page-spot-light', [AdminController::class, 'getAddEditHomePageSpotLight'])->name('get:admin:add_home_page_spot_light');
                Route::get('/edit-home-page-spot-light/{id}', [AdminController::class, 'getAddEditHomePageSpotLight'])->name('get:admin:edit_home_page_spot_light');
                Route::post('/update-home-page-spot-light', [AdminController::class, 'postAdminUpdateHomePageSpotLight'])->name('post:admin:update_home_page_spot_light');
                Route::get('/home-spot-light-change-status', [AdminController::class, 'getAdminHomeSpotLightChangeStatus'])->name('get:admin:change_home_spot_light_status');
                Route::get('/delete-home-page-spot-light', [AdminController::class, 'getAdminDeleteHomeSpotLight'])->name('get:admin:delete_home_spot_light');
                Route::post('/ajax-load-store-provider', [AdminController::class, 'postAjaxLoadStoreProvider'])->name('get:admin:ajax_load_store-provider');

                //Geo Fencing Restricted areas
                Route::get('/restricted-area-list', [AdminController::class, 'getAdminRestrictedAreaList'])->name('get:admin:restricted_area_list');
                Route::get('/add-restricted-area', [AdminController::class, 'getAdminAddRestrictedArea'])->name('get:admin:add_restricted_area');
                Route::get('/edit-restricted-area/{id}', [AdminController::class, 'getAdminEditRestrictedArea'])->name('get:admin:edit_restricted_area');
                Route::post('/update-restricted-area', [AdminController::class, 'postAdminUpdateRestrictedArea'])->name('post:admin:update_restricted_area');
                Route::get('/update-restricted-area-status', [AdminController::class, 'postAdminUpdateRestrictedAreaStatus'])->name('get:admin:update_restricted_area_status');
                Route::get('/delete-restricted-area', [AdminController::class, 'getAdminDeleteRestrictedArea'])->name('get:admin:delete_restricted_area');

                //Email Templates
                Route::get('/email-templates', [AdminController::class, 'getEmailTemplatesList'])->name('get:admin:email_templates');
                Route::get('/add-email-templates', [AdminController::class, 'getAdminAddEmailTemplates'])->name('get:admin:add_email_templates');
                Route::get('/edit-email-templates/{id}', [AdminController::class, 'getAdminEditEmailTemplates'])->name('get:admin:edit_email_templates');
                Route::post('/update-email-templates', [AdminController::class, 'postAdminUpdateEmailTemplates'])->name('post:admin:update_email_templates');
                Route::get('/update-email-templates-status', [AdminController::class, 'postAdminUpdateEmailTemplatesStatus'])->name('get:admin:update_email_templates_status');
                Route::get('/delete-email-templates', [AdminController::class, 'getAdminDeleteEmailTemplates'])->name('get:admin:delete_email_templates');

                //get language lists
                Route::get('/language-lists', [AdminController::class, 'getAdminLanguageLists'])->name('get:admin:language_lists');
                Route::post('/language-lists', [AdminController::class, 'postAdminUpdateLanguageLists'])->name('post:admin:update_language-lists');
                Route::get('/language-lists-status', [AdminController::class, 'getAdminUpdateLanguageLists'])->name('get:ajax:admin:language_lists_status');

                //get language constant
                Route::get('/language-constant', [AdminController::class, 'getAdminLanguageConstant'])->name('get:admin:language_constant');
                Route::post('/language-constant', [AdminController::class, 'postAdminUpdateLanguageConstant'])->name('post:admin:update_language_constant');
                Route::get('/edit-language-constant/{id}', [AdminController::class, 'getAdminEditLanguageConstant'])->name('post:admin:edit_language_constant');

                Route::get('/user-change-password', [AdminController::class, 'getUpdateUserChangePassword'])->name('get:admin:user_change_password');
                Route::get('/provider-change-password', [AdminController::class, 'getUpdateProviderChangePassword'])->name('get:admin:provider_change_password');

                //Report issue
                Route::group(['prefix' => 'report-issue'], function () {
                    //Send message Notification to user to firebase live database
                    Route::get('/send-message-notification/', [ReportIssueController::class, 'sendMessageNotification'])->name('get:admin:send_message_notification');
                    //set web token of admin
                    Route::get('/admin-web-token', [ReportIssueController::class, 'updateWebToken'])->name('get:admin:update_web_token');
                    //Upload image as a message in firebase
                    Route::post('/upload-chat-image', [ReportIssueController::class, 'uploadChatImage'])->name('get:admin:upload_chat_image');
                    //form for Report issue setting
                    Route::get('/setting', [ReportIssueController::class, 'getAdminReportIssueSetting'])->name('get:admin:report_issue_setting');
                    //updating the report issue setting
                    Route::post('/setting', [ReportIssueController::class, 'postAdminUpdateReportIssueSetting'])->name('post:admin:report_issue_setting');
                    //ajax report issue update status
                    Route::get('report-issue/update-report-issue-status', [ReportIssueController::class, 'updateReportIssuesStatus'])->name('get:ajax:admin:update_report_issue_status');
                    //Manage Report issues
                    Route::get('/{slug}', [ReportIssueController::class, 'showReportIssues'])->name('get:admin:report_issue');

                    Route::get('/chat/{id}', [ReportIssueController::class, 'showReportIssueChat'])->name('get:admin:report_issue_chat');
                    //Fetch Report issues with AJAX
                    Route::get('/fetch-report-issue/{providerType}', [ReportIssueController::class, 'getReportIssue'])->name('get:ajax:admin:fetch_report_issue');

                    //Faqs
                    Route::group(['prefix' => 'faqs'], function () {
                        //Manage Faqs
                        Route::get('/manage', [ReportIssueController::class, 'showFaq'])->name('get:admin:faqs');
                        //Fetch Faqs from datatable with AJAX
                        Route::get('/fetchFaqs', [ReportIssueController::class, 'getFaq'])->name('get:ajax:admin:fetch_faq_plan_lists');
                        //Add Faqs form
                        Route::get('/add', [ReportIssueController::class, 'addFaq'])->name('get:admin:add_faq');
                        //Edit Faqs form
                        Route::get('/edit/{id}', [ReportIssueController::class, 'editFaq'])->name('get:admin:edit_faq');
                        //Update or add Faqs record
                        Route::post('/saveUpdateFaqs', [ReportIssueController::class, 'saveUpdateFaq'])->name('post:admin:update_faq');
                        //Update Faqs status via ajax call
                        Route::get('/updateFaqsStatus', [ReportIssueController::class, 'updateFaqStatus'])->name('get:ajax:admin:update_faq_status');
                        //Delete Faqs
                        Route::get('/delete', [ReportIssueController::class, 'deleteFaq'])->name('get:admin:delete_faq');
                    });
                    //report issue details manage
                    Route::get('{id}/{provider_id}', [ReportIssueController::class, 'showReportDetails'])->name('get:admin:detailed_report');
                });

                //order wise chat
                Route::get('{slug}/chat-history/{order_id}', [OrderWiseChatController::class, 'getOrderWiseChat'])->name('get:admin:get_order_wise_chat');
                Route::get('/fetch-chats', [OrderWiseChatController::class, 'getOrderWiseChatAjax'])->name('get:admin:get_order_wise_chat_ajax');

                //other services
                Route::prefix('provider-services')->group(function () {
                    Route::get('service-list', [OtherServiceController::class, 'getOtherServiceList'])->name('get:admin:other_service_list');

                    //other-service dashboard
                    Route::get('/{slug}/dashboard', [OtherServiceController::class, 'getOtherServiceDashboard'])->name('get:admin:other_service_dashboard');
                    //other services list
                    //Route::get('/other-service-list', [OtherServiceController::class, 'getOtherServiceList'])->name('get:admin:other_service_list');

                    Route::get('/add-service-category', [OtherServiceController::class, 'getAddServiceCategory'])->name('get:admin:add_service_category');
                    Route::get('/edit-service/{slug}', [OtherServiceController::class, 'getEditServiceCategory'])->name('get:admin:edit_service_category');
                    //Route::get(/delete-service-category',[OtherServiceController::class, 'getDeleteServiceCategory'])->name('get:admin:delete_service_category');
                    Route::post('/update-service-category', [OtherServiceController::class, 'postUpdateServiceCategory'])->name('post:admin:update_service_category');

                    Route::get('{slug}/provider-list/{status}', [OtherServiceController::class, 'getOtherServicesProviderList'])->name('get:admin:other_service_provider_list');
                    Route::get('{slug}/add-provider', [OtherServiceController::class, 'getOtherServicesAddProvider'])->name('get:admin:other_service_add_provider');
                    Route::get('{slug}/edit-provider/{id}', [OtherServiceController::class, 'getOtherServicesEditProvider'])->name('get:admin:other_service_edit_provider');
                    Route::get('change-provider-status', [OtherServiceController::class, 'getOtherServicesChangeProviderStatus'])->name('get:admin:other_service_change_provider_status');
                    Route::get('delete-provider-service', [OtherServiceController::class, 'getOtherServicesChangeDeleteProvider'])->name('get:admin:other_service_delete_provider');
                    Route::post('/{slug}/update-provider', [OtherServiceController::class, 'postUpdateOtherServiceProvider'])->name('post:admin:update_other_service_provider');
                    Route::get('/update-provider-status', [OtherServiceController::class, 'getUpdateOtherServiceProviderStatus'])->name('get:admin:update_other_service_provider_status');
                    Route::get('change-provider-sponsor', [OtherServiceController::class, 'getOtherServicesChangeProvidersponsor'])->name('get:admin:other_service_change_provider_sponsor');
                    Route::get('/provider-wallet-transaction/{id}', [OtherServiceController::class, 'postAdminProviderWalletTransaction'])->name('get:admin:provider_wallet_transaction');
                    Route::get('/update-provider-wallet-transaction', [OtherServiceController::class, 'postAdminUpdateProviderWalletTransaction'])->name('get:admin:update_provider_wallet_transaction');

                    Route::get('/{slug}/service-setting', [OtherServiceController::class, 'getOtherServiceSetting'])->name('get:admin:other_service_setting');
                    Route::post('/update-service-setting', [OtherServiceController::class, 'postUpdateOtherServiceSetting'])->name('post:admin:update_other_service_setting');

                    //other service sub category list
                    Route::get('/{slug}/sub-category-list', [OtherServiceController::class, 'getOtherServiceSubCategoryList'])->name('get:admin:other_service_sub_category_list');
                    Route::get('/sub-category-change-status', [OtherServiceController::class, 'getOtherServiceSubCategoryChangeStatus'])->name('get:admin:other_service_sub_category_change_status');
                    Route::post('/{slug}/update-category', [OtherServiceController::class, 'getUpdateOtherServiceSubCategory'])->name('get:admin:update_other_service_sub_category');
                    Route::get('/{slug}/edit-sub-category/{id}', [OtherServiceController::class, 'getEditOtherServiceSubCategory'])->name('get:admin:edit_other_service_sub_category');
                    Route::get('/{slug}/add-sub-category', [OtherServiceController::class, 'getAddOtherServiceSubCategory'])->name('get:admin:add_other_service_sub_category');
                    Route::get('/delete-sub-category', [OtherServiceController::class, 'getDeleteOtherServiceSubCategory'])->name('get:admin:delete_other_service_sub_category');

                    //other service order list
                    Route::get('/{slug}/order-list/{status}', [OtherServiceController::class, 'getOtherServiceOrderList'])->name('get:admin:other_service_order_list');
                    //                        Route::get('/{slug}/order-details/', [OtherServiceController::class, 'getOtherServiceOrderDetails'])->name('get:admin:other_service_order_details');
                    Route::get('/{slug}/order-details/{id}', [OtherServiceController::class, 'getOtherServiceOrderDetails'])->name('get:admin:other_service_order_details');
                    Route::get('/{slug}/provider-order-list/{id}', [OtherServiceController::class, 'getOtherServiceProviderOrderList'])->name('get:admin:other_service_provider_order_list');

                    Route::get('/other-service-user-refund-amount-settle', [OtherServiceController::class, 'getUserRefundAmountSettle'])->name('get:admin:other_service_user_refund_amount_settle');

                    Route::get('/update-order-status', [OtherServiceController::class, 'getUpdateOtherServiceOrderStatus'])->name('get:admin:update_other_service_order_status');

                    //store review list
                    Route::get('/review-list', [OtherServiceController::class, 'getOtherServiceReviewList'])->name('get:admin:review_list');
                    Route::get('/{slug}/provider-review-list/{id}', [OtherServiceController::class, 'getOtherServiceProviderReviewList'])->name('get:admin:provider_review_list');

                    Route::get('/provider-review-change-status', [OtherServiceController::class, 'getOtherServiceProviderReviewChangeStatus'])->name('get:admin:provider_review_change_status');
                    Route::get('/delete-provider-review', [OtherServiceController::class, 'getDeleteOtherServiceProviderReview'])->name('get:admin:delete_provider_review');

                    //other service product listb
                    Route::get('/{slug}/provider-package-list/{id}', [OtherServiceController::class, 'getOtherServiceProviderPackageList'])->name('get:admin:provider_package_list');
                    Route::get('/{slug}/provider-add-package/{id}', [OtherServiceController::class, 'getAddOtherServiceProviderPackage'])->name('get:admin:provider_add_package');
                    Route::post('/{slug}/provider-update-package', [OtherServiceController::class, 'postUpdateOtherServiceProviderPackage'])->name('post:admin:provider_update_package');
                    Route::get('/{slug}/provider-edit-package/{id}', [OtherServiceController::class, 'getEditOtherServiceProviderPackage'])->name('get:admin:provider_edit_package');
                    Route::get('/{slug}/provider-package/change-status', [OtherServiceController::class, 'getChangeStatusOtherServiceProviderPackage'])->name('get:admin:provider_package_change_status');
                    Route::get('/provider-delete-package', [OtherServiceController::class, 'getDeleteOtherServiceProviderPackage'])->name('get:admin:provider_delete_package');

                    Route::get('/{slug}/provider-document/{id}', [OtherServiceController::class, 'getOtherServiceProviderDocument'])->name('get:admin:provider_document');
                    Route::post('/{slug}/provider-document/{id}', [OtherServiceController::class, 'postOtherServiceProviderDocument'])->name('post:admin:provider_document');

                    //other service required document list
                    Route::get('/{slug}/required-document-list/', [AdminController::class, 'getRequiredDocumentList'])->name('get:admin:other_service_required_document_list');
                    Route::get('/{slug}/add-required-document/', [AdminController::class, 'getAddRequiredDocument'])->name('get:admin:other_service_add_required_document');
                    Route::get('/{slug}/edit-required-document/{id}', [AdminController::class, 'getEditRequiredDocument'])->name('get:admin:other_service_edit_required_document');
                    Route::post('/{slug}/update-required-document', [AdminController::class, 'postUpdateRequiredDocument'])->name('post:admin:other_service_update_required_document');

                    Route::get('/{slug}/earning-report', [OtherServiceController::class, 'getOtherServiceEarningReport'])->name('get:admin:other_service_earning_report');
                    Route::post('/{slug}/earning-report', [OtherServiceController::class, 'postOtherServiceEarningReport'])->name('post:admin:other_service_earning_report');
                    Route::get('/{slug}/order-payment-settle', [OtherServiceController::class, 'postOtherServiceOrderPaymentSettle'])->name('post:admin:other_service_order_payment_settle');

                    Route::get('/{slug}/promocode-list', [AdminController::class, 'getAdminPromocodeList'])->name('get:admin:other:promocode_list');
                    Route::get('/{slug}/add-promocode', [AdminController::class, 'getAdminAddPromocode'])->name('get:admin:other:add_promocode');
                    Route::get('/{slug}/edit-promocode/{id}', [AdminController::class,  'getAdminEditPromocode'])->name('get:admin:other:edit_promocode');
                    Route::post('/{slug}/update-promocode', [AdminController::class, 'postAdminUpdatePromocode'])->name('post:admin:other:update_promocode');
                    Route::get('/promocode-change-status', [AdminController::class, 'getAdminPromocodeChangeStatus'])->name('get:admin:other:promocode_change_status');

                    //code for service slider for on demand service
                    Route::get('/{slug}/service-slider-list', [OtherServiceController::class, 'getOnDemandServiceSliderList'])->name('get:admin:on_demand_service_slider_list');
                    Route::get('/{slug}/add-service-slider', [OtherServiceController::class, 'getAddOnDemandServiceSlider'])->name('get:admin:add_on_demand_service_slider');
                    Route::get('/{slug}/edit-service-slider/{id}', [OtherServiceController::class, 'getEditOnDemandServiceSlider'])->name('get:admin:edit_on_demand_service_slider');
                    Route::post('/{slug}/update-service-slider', [OtherServiceController::class, 'postUpdateOnDemandServiceSlider'])->name('post:admin:update_on_demand_service_slider');
                    Route::get('/update-service-slider-status', [OtherServiceController::class, 'postUpdateOnDemandServiceSliderStatus'])->name('get:ajax:admin:update_on_demand_service_slider_status');
                    Route::get('/delete-service-slider', [OtherServiceController::class, 'postDeleteOnDemandServiceSlider'])->name('get:ajax:admin:delete_on_demand_service_slider');
                });
            });
        });
    });
});


Route::prefix('billing-admin')->group(function () {
    Route::group(['middleware' => 'auth:admin'], function () {
        Route::get('/logout/{admin}', [LoginController::class, 'logout'])->name('admin:logout');
        Route::group(['middleware' => 'revalidate'], function () {
            Route::group(['middleware' => 'adminrole'], function () {
                Route::get('/change-password', [ResetPasswordController::class, 'getAccountAdminChangePassword'])->name('get:account:change_password');
                Route::post('/change-password', [ResetPasswordController::class, 'postAccountAdminChangePassword'])->name('post:account:change_password');
                Route::get('/dashboard', [AccountAdminController::class, 'getAdminDashboard'])->name('get:account:dashboard');

                Route::get('/profile', [AccountAdminController::class, 'getAdminProfile'])->name('get:account:profile');
                Route::post('/update-profile', [AccountAdminController::class, 'postAdminProfile'])->name('post:account:profile');

                //order list
                Route::get('/order-list', [AccountAdminController::class, 'getOtherServiceOrderList'])->name('get:account:other_service_order_list');
                Route::get('/service-order-details/{id}', [AccountAdminController::class, 'getOtherServiceOrderDetails'])->name('get:account:other_service_order_details');

                Route::get('/services-earning-report', [AccountAdminController::class, 'getOtherServiceEarningReport'])->name('get:account:other_service_earning_report');
                Route::post('/services-earning-report', [AccountAdminController::class, 'postOtherServiceEarningReport'])->name('post:account:other_service_earning_report');
                Route::get('/services-order-payment-settle', [AccountAdminController::class, 'postOtherServiceOrderPaymentSettle'])->name('post:account:other_service_order_payment_settle');
            });
        });
    });
});

Route::group(['middleware' => 'revalidate'], function () {
    Route::get('/provider-admin/login', [AuthPagesController::class, 'getProvideLogin'])->name('get:provider-admin:login');
    Route::get('/provider-admin/register', [AuthPagesController::class, 'getProvideRegister'])->name('get:provider-admin:register');
    Route::get('/provider-admin/auth/google', [LoginController::class, 'redirectToGoogle'])->name('get:provider-admin:google:login');
    Route::get('/provider-admin/auth/google/callback', [LoginController::class, 'handleGoogleCallback'])->name('get:provider-admin:google-callback:login');
});

Route::prefix('provider-admin')->group(function () {
    Route::post('/login', [LoginController::class, 'postProviderAdminLogin'])->name('post:provider-admin:login');
    Route::post('/register', [RegisterController::class, 'postProviderRegister'])->name('post:provider-admin:register');

    Route::group(['middleware' => 'auth:on_demand'], function () {
        Route::get('/logout/{provider}', [LoginController::class, 'logout'])->name('provider:logout');
        Route::group(['middleware' => 'revalidate'], function () {
            Route::get('/dashboard', [OtherServiceAdminController::class, 'getDashboard'])->name('get:provider-admin:dashboard');
            Route::get('/service-register', [OtherServiceAdminController::class, 'getProviderServiceRegister'])->name('get:provider-admin:service-register');
            Route::post('/service-register', [OtherServiceAdminController::class, 'postProviderServiceRegister'])->name('post:provider-admin:service-register');
            Route::get('/get-service-sub-category-document', [OtherServiceAdminController::class, 'getServiceSubCategoryDocument'])->name('get:provider-admin:get-service-sub-category-document');
            /*OTP Verification*/
            Route::get('/not-verified', [OtherServiceAdminController::class, 'getProviderNotVerified'])->name('get:provider-admin:not_verified');
            Route::get('/resend-verification-link', [OtherServiceAdminController::class, 'getProviderResendVerificationLink'])->name('get:provider-admin:resend_verification_link');
            Route::post('/verification-account', [OtherServiceAdminController::class, 'postProviderOtpVerification'])->name('post:provider-admin:account_verification_approval');

            Route::get('/edit-profile', [OtherServiceAdminController::class, 'getProviderServiceEditProfile'])->name('get:provider-admin:edit-profile');
            Route::post('/edit-profile', [OtherServiceAdminController::class, 'postProviderServiceEditProfile'])->name('post:provider-admin:edit-profile');

            Route::get('/change-password', [ResetPasswordController::class, 'getProviderAdminChangePassword'])->name('get:provider-admin:change_password');
            Route::post('/change-password', [ResetPasswordController::class, 'postProviderAdminChangePassword'])->name('post:provider-admin:change_password');

            Route::get('/services', [OtherServiceAdminController::class, 'getProviderServices'])->name('get:provider-admin:services');
            Route::get('/add-service', [OtherServiceAdminController::class, 'getProviderAddServices'])->name('get:provider-admin:add_services');
            Route::post('/add-service', [OtherServiceAdminController::class, 'postProviderAddServices'])->name('post:provider-admin:add_services');
            Route::get('/delete-service', [OtherServiceAdminController::class, 'postProviderDeleteService'])->name('post:provider-admin:delete_service');
            Route::get('/update-service-current-status', [OtherServiceAdminController::class, 'getProviderUpdateServiceCurrentStatus'])->name('get:provider-admin:update_service_current_status');

            Route::get('{slug}/dashboard', [OtherServiceAdminController::class, 'getProviderServiceDashboard'])->name('get:provider-admin:service-dashboard');
            Route::get('{slug}/package-list', [OtherServiceAdminController::class, 'getProviderServicePackageList'])->name('get:provider-admin:service-package-list');
            Route::get('{slug}/add-package', [OtherServiceAdminController::class, 'getProviderServiceAddPackage'])->name('get:provider-admin:add-package');
            Route::get('{slug}/edit-package/{id}', [OtherServiceAdminController::class, 'getProviderServiceEditPackage'])->name('get:provider-admin:edit-package');
            Route::post('{slug}/update-package', [OtherServiceAdminController::class, 'getProviderServiceUpdatePackage'])->name('get:provider-admin:update-package');
            Route::get('/update-package-status', [OtherServiceAdminController::class, 'getProviderServiceUpdatePackageStatus'])->name('get:provider-admin:update-package-status');
            Route::get('/delete-package', [OtherServiceAdminController::class, 'getProviderServiceDeletePackage'])->name('get:provider-admin:delete-package');

            Route::get('/manage-card', [OtherServiceAdminController::class, 'getProviderManageCard'])->name('get:provider-admin:manage_card');
            Route::post('update-card', [OtherServiceAdminController::class, 'postProviderUpdateCard'])->name('post:provider-admin:update_card');
            Route::get('/delete-card', [OtherServiceAdminController::class, 'getProviderDeleteCard'])->name('get:provider-admin:delete_card');

            Route::get('/wallet/{page?}', [OtherServiceAdminController::class, 'getProviderWallet'])->name('get:provider-admin:wallet');
            Route::post('/wallet', [OtherServiceAdminController::class, 'postProviderWallet'])->name('post:provider-admin:wallet');

            Route::get('/{slug}/order-list/{status}', [OtherServiceAdminController::class, 'getProviderOtherServiceOrderList'])->name('get:provider-admin:other_service_order_list');
            Route::get('/{slug}/order-details/{id}', [OtherServiceAdminController::class, 'getProviderOtherServiceOrderDetails'])->name('get:provider-admin:other_service_order_details');

            Route::get('/provider-document/{slug}', [OtherServiceAdminController::class, 'getProviderOtherServiceDocument'])->name('get:provider-admin:other_service_provider_document');
            Route::post('/provider-document/{slug}', [OtherServiceAdminController::class, 'postProviderOtherServiceDocument'])->name('post:provider-admin:other_service_provider_document');

            Route::get('/order-list/{status}', [OtherServiceAdminController::class, 'getProviderOtherServiceAllOrderList'])->name('get:provider-admin:other_service_all_order_list');
            Route::get('/order-details/{id}', [OtherServiceAdminController::class, 'getProviderOtherServiceAllOrderDetails'])->name('get:provider-admin:other_service_order_details');

            Route::get('/{slug}/earning-report', [OtherServiceAdminController::class, 'getProviderOtherServiceEarningReport'])->name('get:provider-admin:other_service_earning_report');
            Route::post('/{slug}/earning-report', [OtherServiceAdminController::class, 'postProviderOtherServiceEarningReport'])->name('post:provider-admin:other_service_earning_report');

            Route::get('/update-order-status', [OtherServiceAdminController::class, 'getUpdateOtherServiceOrderStatus'])->name('get:provider:update_other_service_order_status');

            /* Portfolio route */
            Route::get('/portfolio/{slug}', [OtherServiceAdminController::class, 'getProviderOtherServicePortfolio'])->name('get:provider-admin:other_service_portfolio');
            Route::post('/portfolio/{slug}', [OtherServiceAdminController::class, 'postProviderOtherServicePortfolio'])->name('post:provider-admin:other_service_portfolio');
            Route::get('/delete-portfolio', [OtherServiceAdminController::class, 'getOtherServiceProviderDeletePortfolio'])->name('get:provider-admin:other_service_provider_delete_portfolio');
        });
    });
});
