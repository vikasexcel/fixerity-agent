<?php

use App\Http\Controllers\Api\ReportIssueController;
use Illuminate\Http\Request;

use App\Http\Controllers\Api\CustomerApiController;
use App\Http\Controllers\Api\Auth\LoginController;
use App\Http\Controllers\Api\Auth\RegisterController;
use App\Http\Controllers\Api\OtherService\BuyerJobController;
use App\Http\Controllers\Api\OtherService\UserController;
use App\Http\Controllers\Api\OtherService\ProviderController;
use App\Http\Controllers\Api\Auth\UpdateRegisterController;
use App\Http\Controllers\Api\Auth\ResetPasswordController;
use App\Http\Controllers\Api\Auth\LogoutController;
use App\Http\Controllers\Api\ProviderApiController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});
Route::group(['middleware' => 'setLocaleLang'], function () {
    Route::middleware('api')->group(function () {

        Route::post('/google-map', [CustomerApiController::class,'postFirebaseSecurityRules'])->name('post:customer:firebase_security_rules');

        //Google Map Api for Autocomplete or auto suggestion
        Route::post('/google-autocomplete-places', [CustomerApiController::class, 'postAutocompleteGooglePlaces']);
        //Google map Api for place details with Unique place id
        Route::post('/google-place-detail', [CustomerApiController::class, 'postGooglePlaceDetails']);
        //Google map Api for route details from point A to point B
        Route::post('/google-route-detail', [CustomerApiController::class, 'postGoogleRouteDetails']);

        /*   REPORT ISSUES START   */
        Route::prefix('report-issue')->group(function () {
            // Report Issue Faqs List
            Route::post('/faqs', [ReportIssueController::class,'postReportIssueFaqsList'])->name('post:report_issue_faqs_list');
            // Save Report Issue as draft
            Route::post('/draft', [ReportIssueController::class,'postReportIssueDraft'])->name('post:report_issue_draft');
            //Update Report Issue
            Route::post('/update', [ReportIssueController::class,'postUpdateReportIssue'])->name('post:update_report_issue');
            //Upload Report Issue Image
            Route::post('/upload-image', [ReportIssueController::class,'postReportIssueUploadImage'])->name('post:report_issue_upload_image');
            //Remove Report Issue Image
            Route::post('/remove-image', [ReportIssueController::class,'postReportIssueRemoveImage'])->name('post:report_issue_remove_image');
            //Shows Report Issue Details
            Route::post('/details', [ReportIssueController::class,'postReportIssueDetails'])->name('post:report_issue_details');
            //Report Issue On Demand History
            Route::post('/on-demand-history', [ReportIssueController::class,'postOnDemandReportIssueHistory'])->name('post:report_issue_on_demand_history');
            //General Report Issue History
            Route::post('/general-history', [ReportIssueController::class,'postGeneralReportIssueHistory'])->name('post:report_issue_on_demand_history');

            //Upload Chat Photo
            Route::post('/chat-photos', [ReportIssueController::class, 'uploadChatPhoto']);
            //Deleting Chat Photo
            Route::post('/delete-chat-photos', [ReportIssueController::class, 'deleteChatPhoto']);
        });
        /*   REPORT ISSUES END   */

        Route::prefix('customer')->group(function () {
            Route::post('/remove-account',[CustomerApiController::class,'postUserRemoveAccount'])->name('post:customer:remove_account');
            Route::post('/app-version-check', [CustomerApiController::class,'postAppVersionCheck'])->name('post:app-version-check');
            Route::post('/country-and-currency-list', [CustomerApiController::class,'postCountryAndCurrencyList'])->name('post:customer:country-and-currency-list');
            Route::post('/facebook-delete-customer',[CustomerApiController::class,'postFacebookUserDataDeletion'])->name('post:user:facebook_data_deletion');
            Route::post('/login', [LoginController::class,'postCustomerLogin'])->name('post:customer:login');
            Route::post('/register', [RegisterController::class,'postCustomerRegister'])->name('post:customer:register');
            Route::post('/edit-profile', [UpdateRegisterController::class,'postUpdateCustomerDetails'])->name('post:customer:update_details');
            Route::post('/change-password', [ResetPasswordController::class,'postCustomerChangePassword'])->name('post:customer:change_password');
            Route::post('/update-country-and-currency', [UpdateRegisterController::class,'postCustomerUpdateCountryAndCurrency'])->name('post:customer:update-country-and-currency');
            Route::post('/logout', [LogoutController::class,'postCustomerLogout'])->name('post:customer:logout');

            Route::post('/contact-verification', [UpdateRegisterController::class,'postCustomerContactVerification'])->name('post:customer:contact_verification');
            Route::post('/resend-otp-verification', [UpdateRegisterController::class,'postCustomerResendOtpVerification'])->name('post:customer:resend_otp_verification');

            Route::post('/forgot-password-request', [ResetPasswordController::class,'postCustomerForgotPasswordRequest'])->name('post:customer:forgot_password_request');
            Route::post('/forgot-change-password', [ResetPasswordController::class,'postCustomerForgotChangePassword'])->name('post:customer:forgot_change_password');

            Route::post('/home', [CustomerApiController::class,'postHomepage'])->name('post:customer:homepage');
            Route::post('/user-details', [CustomerApiController::class,'postUserDetails'])->name('post:customer:user_details');

            Route::post('/add-card', [CustomerApiController::class,'postCustomerAddCard'])->name('post:customer:add_card');
            Route::post('/delete-card', [CustomerApiController::class,'postCustomerRemoveCard'])->name('post:customer:remove_card');
            Route::post('/card-list', [CustomerApiController::class,'postCustomerCardList'])->name('post:customer:card_list');

            Route::post('/add-address', [CustomerApiController::class,'postCustomerAddAddress'])->name('post:customer:add_address');
            Route::post('/edit-address', [CustomerApiController::class,'postCustomerEditAddress'])->name('post:customer:edit_address');
            Route::post('/delete-address', [CustomerApiController::class,'postCustomerDeleteAddress'])->name('post:customer:delete_address');
            Route::post('/address-list', [CustomerApiController::class,'postCustomerAddressList'])->name('post:customer:address_list');

            Route::post('/add-wallet-balance', [CustomerApiController::class,'postCustomerAddWalletBalance'])->name('post:customer:add_wallet_balance');
            Route::post('/wallet-transaction', [CustomerApiController::class,'postCustomerWalletTransaction'])->name('post:customer:wallet_transaction');
            Route::post('/get-wallet-balance', [CustomerApiController::class,'postCustomerGetWalletBalance'])->name('post:customer:get_wallet_balance');

            Route::post('/mass-notification-list', [CustomerApiController::class,'postCustomerMassNotificationList'])->name('post:customer:get_mass_notification_list');

            Route::post('/search-wallet-transfer-user-list', [CustomerApiController::class,'postCustomerSearchWalletTransferUserList'])->name('post:customer:search_wallet_transfer_user_list');
            Route::post('/wallet-transfer', [CustomerApiController::class,'postCustomerWalletToWalletTransfer'])->name('post:customer:wallet_to_wallet_transfer');

            Route::post('/get-promocode-list', [CustomerApiController::class,'postStorePromocodeList'])->name('post:customer:get_promocode_list');

            Route::post('/change-contact-number', [UpdateRegisterController::class,'postCustomerChangeContactNumber'])->name('post:customer:change_contact_number');

            Route::post('/home-page-spot-light-list', [CustomerApiController::class,'postHomePageSpotLightList'])->name('post:home_page_spot_light_list');

            Route::prefix('on-demand')->group(function () {
                Route::post('/home', [UserController::class,'postOtherServiceHome'])->name('post:other_service:home');
                Route::post('/category-list', [UserController::class,'postOtherServiceCategoryList'])->name('post:other_service:home');
                Route::post('/provider-package-list', [UserController::class,'postOtherServiceProviderPackageList'])->name('post:other_service:provider_package_list');
                Route::post('/provider-details', [UserController::class,'postOtherServiceProviderDetails'])->name('post:other_service:provider_details');
                Route::post('/provider-time-list', [UserController::class,'postOtherServiceProviderTimeList'])->name('post:other_service:provider_time_list');
                Route::post('/provider-list', [UserController::class,'postOtherServiceProviderList'])->name('post:other_service:provider_list');
                Route::post('/place-order', [UserController::class,'postOtherServicePlaceOrder'])->name('post:other_service:place_order');
                Route::post('/order-preview', [UserController::class,'postOtherServiceOrderPreview'])->name('post:other_service:order_preview');
                Route::post('/order-history', [UserController::class,'postOtherServiceOrderHistory'])->name('post:other_service:order_history');
                Route::post('/order-details', [UserController::class,'postOtherServiceOrderDetails'])->name('post:other_service:order_details');
                Route::post('/order-payment', [UserController::class,'postOtherServiceOrderPayment'])->name('post:other_service:order_payment');

                Route::post('/order-cancelled', [UserController::class,'postOtherServiceOrderCancelled'])->name('post:other_service:order_cancelled');

                Route::post('/add-tip', [UserController::class,'postOtherServiceOrderAddTip'])->name('post:other_service:order_add_tip');
                Route::post('/order-rating', [UserController::class,'postOtherServiceOrderRating'])->name('post:other_service:order_rating');
                Route::post('/provider-review', [UserController::class,'postOtherServiceProviderReview'])->name('post:other_service:provider_review');
                Route::post('/provider-gallery', [UserController::class,'postOtherServiceProviderGallery'])->name('post:other_service:provider_gallery');
                Route::post('/job/create', [BuyerJobController::class,'create'])->name('post:other_service:job_create');
                Route::post('/job/list', [BuyerJobController::class,'list'])->name('post:other_service:job_list');
                Route::post('/job/update-status', [BuyerJobController::class,'updateStatus'])->name('post:other_service:job_update_status');
            });

            Route::post('/support-pages', [CustomerApiController::class,'postMyCheckoutSupportPages'])->name('post:support_pages');
        });

        Route::prefix('on-demand')->group(function () {
            Route::post('/remove-account', [CustomerApiController::class,'postProviderRemoveAccount'])->name('post:on_demand:remove_account');
            Route::post('/app-version-check', [CustomerApiController::class,'postAppVersionCheck'])->name('post:app-version-check');
            Route::post('/country-and-currency-list', [CustomerApiController::class,'postCountryAndCurrencyList'])->name('post:on_demand:country-and-currency-list');
            Route::post('/facebook-delete-provider', [CustomerApiController::class,'postFacebookProviderDataDeletion'])->name('post:driver:facebook_data_deletion');
            Route::post('/login', [LoginController::class,'postOnDemandLogin'])->name('post:on_demand:login');
            Route::post('/register', [RegisterController::class,'postOnDemandRegister'])->name('post:on_demand:register');
            Route::post('/social-required-field', [UpdateRegisterController::class,'postOnDemandSocialRequiredField'])->name('post:on_demand:social_required_field');
            Route::post('/edit-profile', [UpdateRegisterController::class,'postUpdateOnDemandProviderDetails'])->name('post:on_demand:edit_profile');
            Route::post('/change-password', [ResetPasswordController::class,'postProviderChangePassword'])->name('post:on_demand:change_password');
            Route::post('/update-country-and-currency', [UpdateRegisterController::class,'postProviderUpdateCountryAndCurrency'])->name('post:on_demand:update-country-and-currency');
            Route::post('/logout', [LogoutController::class,'postProviderLogout'])->name('post:on_demand:logout');

            Route::post('/contact-verification', [UpdateRegisterController::class,'postOnDemandContactVerification'])->name('post:on_demand:contact_verification');
            Route::post('/resend-otp-verification', [UpdateRegisterController::class,'postOnDemandResendOtpVerification'])->name('post:on_demand:resend_otp_verification');

            Route::post('/forgot-password-request', [ResetPasswordController::class,'postOnDemandForgotPasswordRequest'])->name('post:on_demand:forgot_password_request');
            Route::post('/forgot-change-password', [ResetPasswordController::class,'postOnDemandForgotChangePassword'])->name('post:on_demand:forgot_change_password');

            Route::post('/provider-service-register-step', [ProviderController::class,'postOnDemandProviderServiceRegisterStep'])->name('post:on_demand:provider_service_register_step');

            Route::post('/mass-notification-list', [ProviderApiController::class,'postOnDemandMassNotificationList'])->name('post:on_demand:get_mass_notification_list');

            Route::post('/get-service-list', [ProviderController::class,'postOnDemandServiceList'])->name('post:on_demand:service_list');
            Route::post('/provider-service-list', [ProviderController::class,'postOnDemandProviderServiceList'])->name('post:on_demand:provider_service_list');
            Route::post('/provider-service-data', [ProviderController::class,'postOnDemandProviderServiceData'])->name('post:on_demand:provider_service_data');
            Route::post('/provider-basic-details', [ProviderController::class,'postOnDemandProviderBasicDetails'])->name('post:on_demand:provider_basic_details');
            Route::post('/public/provider-service-by-category', [ProviderController::class,'postPublicProviderServiceByCategory'])->name('post:on_demand:public_provider_service_by_category');
            Route::post('/update-agent-config', [ProviderController::class,'postOnDemandUpdateAgentConfig'])->name('post:on_demand:update_agent_config');
            Route::post('/add-services', [ProviderController::class,'postOnDemandAddServices'])->name('post:on_demand:add_services');
            Route::post('/change-service-current-status', [ProviderController::class,'postOnDemandChangeServiceCurrentStatus'])->name('post:on_demand:change_service_current_status');
            Route::post('/remove-service', [ProviderController::class,'postOnDemandRemoveService'])->name('post:on_demand:remove_service');
            Route::post('/package-list', [ProviderController::class,'postOnDemandPackageList'])->name('post:on_demand:package_list');
            Route::post('/get-category-list', [ProviderController::class,'postOnDemandGetCategoryList'])->name('post:on_demand:get_category_list');
            Route::post('/get-subcategory-list', [ProviderController::class,'postOnDemandGetSubcategoryList'])->name('post:on_demand:get_subcategory_list');
            Route::post('/add-update-package', [ProviderController::class,'postOnDemandAddUpdatePackage'])->name('post:on_demand:add_update_package');
            Route::post('/change-package-status', [ProviderController::class,'postOnDemandChangePackageStatus'])->name('post:on_demand:change_package_status');
            Route::post('/delete-package', [ProviderController::class,'postOnDemandDeletePackage'])->name('post:on_demand:delete_package');
            Route::post('/document-list', [ProviderController::class,'postOnDemandDocumentList'])->name('post:on_demand:document_list');

            Route::post('/order-feedback', [ProviderController::class,'postGetOnDemandFeedback'])->name('post:on_demand:get_order_feedback');

            Route::post('/upload-single-document', [ProviderController::class,'postOnDemandUploadSingleDocument'])->name('post:on_demand:upload_single_document');

            Route::post('/home', [ProviderController::class,'postOnDemandHome'])->name('post:on_demand:home');
            Route::post('/update-order-status', [ProviderController::class,'postOnDemandUpdateOrderStatus'])->name('post:on_demand:update_order_status');
            Route::post('/order-collect-payment', [ProviderController::class,'postOnDemandOrderCollectPayment'])->name('post:on_demand:order_collect_payment');

            Route::post('/order-details', [ProviderController::class,'postOnDemandOrderDetails'])->name('post:on_demand:order_details');

            Route::post('/order-rating', [ProviderController::class,'postOnDemandOrderRating'])->name('post:on_demand:order_rating');

            Route::post('/order-history', [ProviderController::class,'postOnDemandOrderHistory'])->name('post:on_demand:order_history');
            Route::post('/change-current-status', [ProviderController::class,'postOnDemandChangeCurrentStatus'])->name('post:on_demand:change_current_status');

            Route::post('/update-bank-details', [ProviderController::class,'postUpdateProviderBankDetails'])->name('post:on_demand:update_bank_history');
            Route::post('/get-bank-details', [ProviderController::class,'postGetProviderBankDetails'])->name('post:on_demand:get_bank_history');

            Route::post('/support-pages', [CustomerApiController::class,'postMyServiceSupportPages'])->name('post:support_pages');

            Route::post('/change-contact-number', [UpdateRegisterController::class,'postProviderChangeContactNumber'])->name('post:provider:change_contact_number');

            Route::post('/upload-portfolio-image', [ProviderController::class,'postOnDemandUploadPortfolioImage'])->name('post:on_demand:upload_portfolio_image');
            Route::post('/delete-portfolio-image', [ProviderController::class,'postOnDemandDeletePortfolioImage'])->name('post:on_demand:delete_portfolio_image');

            Route::post('/add-card', [ProviderApiController::class,'postOnDemandAddCard'])->name('post:on_demand:add_card');
            Route::post('/delete-card', [ProviderApiController::class,'postOnDemandRemoveCard'])->name('post:on_demand:remove_card');
            Route::post('/card-list', [ProviderApiController::class,'postOnDemandCardList'])->name('post:on_demand:card_list');

            //Cash-Out Request Api
            Route::post('/request-cash-out', [ProviderApiController::class,'postOnDemandRequestCashout'])->name('post:on_demand:request_cash_out');

            Route::post('/add-wallet-balance', [ProviderApiController::class,'postOnDemandAddWalletBalance'])->name('post:on_demand:add_wallet_balance');
            Route::post('/wallet-transaction', [ProviderApiController::class,'postOnDemandWalletTransaction'])->name('post:on_demand:wallet_transaction');
            Route::post('/get-wallet-balance', [ProviderApiController::class,'postOnDemandGetWalletBalance'])->name('post:on_demand:get_wallet_balance');

            Route::post('/search-wallet-transfer-user-list', [ProviderApiController::class,'postOnDemandSearchWalletTransferUserList'])->name('post:on_demand:search_wallet_transfer_user_list');
            Route::post('/wallet-transfer', [ProviderApiController::class,'postOnDemandWalletToWalletTransfer'])->name('post:on_demand:wallet_to_wallet_transfer');

            Route::post('/update-open-time', [ProviderApiController::class,'postOnDemandUpdateOpenTime'])->name('post:on_demand:update_open_time');
            Route::post('/update-work-schedule', [ProviderApiController::class,'postOnDemandUpdateWorkSchedule'])->name('post:on_demand:update_work_schedule');
            Route::post('/update-work-status', [ProviderApiController::class,'postOnDemandUpdateWorkStatus'])->name('post:on_demand:update_work_status');
            Route::post('/open-time-list', [ProviderApiController::class,'postOnDemandOpenTimeList'])->name('post:on_demand:open_time_list');
        });

    });
});

