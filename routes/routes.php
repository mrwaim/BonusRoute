<?php

Route::group(['prefix' => 'bonus-management', 'as' => 'bonus-management.','middleware' => 'config:has_bonus'], function () {
    Route::group(['middleware' => ['auth']], function () {
        Route::get('choose-payout/{bonus_id}/{bonus_payout_id}', '\Klsandbox\BonusRoute\Http\Controllers\BonusManagementController@getChoosePayout');
        Route::get('view/{bonus_id}', '\Klsandbox\BonusRoute\Http\Controllers\BonusManagementController@getView');
        Route::get('list/{filter}', '\Klsandbox\BonusRoute\Http\Controllers\BonusManagementController@getList');

        //bonus-management.bulk-pay
        Route::get('bulk-pay/{monthlyReportId}/{type}', ['as' => 'bulk-pay', 'uses' => '\Klsandbox\BonusRoute\Http\Controllers\BonusManagementController@getBillplzBulkPay']);
        //bonus-management.payment-state
        Route::get('payment-state', ['as' => 'payment-state', 'uses' => '\Klsandbox\BonusRoute\Http\Controllers\BonusManagementController@paymentState']);
    });

    Route::group(['middleware' => ['role:manager']], function () {
        Route::get('list-payments/{year}/{month}/{is_hq}/{organization_id}/{filter}', '\Klsandbox\BonusRoute\Http\Controllers\BonusManagementController@getListPayments');
        Route::get('bonus-payments-list/{filter}', '\Klsandbox\BonusRoute\Http\Controllers\BonusManagementController@getBonusPaymentsList');
        Route::post('set-approvals-all', '\Klsandbox\BonusRoute\Http\Controllers\BonusManagementController@postSetApprovalsAll');
        Route::get('set-payments-approvals', '\Klsandbox\BonusRoute\Http\Controllers\BonusManagementController@getSetPaymentsApprovals');

        Route::get('list-orders/{year}/{month}/{is_hq}/{organization_id}', '\Klsandbox\BonusRoute\Http\Controllers\BonusManagementController@getListOrders');
        Route::get('list-bonuses/{year}/{month}/{is_hq}/{organization_id}', '\Klsandbox\BonusRoute\Http\Controllers\BonusManagementController@getListBonuses');
        Route::get('list-user-bonuses/{user_id}/{monthly_user_report_id}', '\Klsandbox\BonusRoute\Http\Controllers\BonusManagementController@getListUserBonuses');
    });

    Route::group(['middleware' => ['role:admin']], function () {
        Route::post('cancel-bonus', '\Klsandbox\BonusRoute\Http\Controllers\BonusManagementController@postCancelBonus');
        Route::get('excel/{monthly_report_id}/{type}', '\Klsandbox\BonusRoute\Http\Controllers\BonusManagementController@getExcel');
        Route::get('txt/{monthly_report_id}/{type}', '\Klsandbox\BonusRoute\Http\Controllers\BonusManagementController@getExport');


        /**
         * Bonus categories
         */
        // listing bonus category
        Route::get('list-bonus-categories', '\Klsandbox\BonusRoute\Http\Controllers\BonusManagementController@getListBonusCategories');

        // create bonus categories
        Route::get('create-bonus-category', '\Klsandbox\BonusRoute\Http\Controllers\BonusManagementController@getCreateBonusCategory');
        Route::post('create-bonus-category', '\Klsandbox\BonusRoute\Http\Controllers\BonusManagementController@postCreateBonusCategory');

        //delete bonus category
        Route::get('delete-bonus-category/{bonus_category}', '\Klsandbox\BonusRoute\Http\Controllers\BonusManagementController@getDeleteBonusCategory');
    });
});
