<?php

Route::group(['prefix' => 'bonus-management', 'as' => 'bonus-management.','middleware' => 'config:has_bonus'], function () {
    Route::group(['middleware' => ['auth']], function () {
        Route::get('choose-payout/{bonus_id}/{bonus_payout_id}', '\Klsandbox\BonusRoute\Http\Controllers\BonusManagementController@getChoosePayout');
        Route::get('view/{bonus_id}', '\Klsandbox\BonusRoute\Http\Controllers\BonusManagementController@getView');
        Route::get('list/{filter}', '\Klsandbox\BonusRoute\Http\Controllers\BonusManagementController@getList');

        //bonus-management.bulk-pay
        Route::get('bulk-pay/{monthlyReportId}/{type}', ['as' => 'bulk-pay', 'uses' => '\Klsandbox\BonusRoute\Http\Controllers\BonusManagementController@bulkPay']);
        //bonus-management.payment-state
        Route::get('payment-state', ['as' => 'payment-state', 'uses' => '\Klsandbox\BonusRoute\Http\Controllers\BonusManagementController@paymentState']);
    });

    Route::group(['middleware' => ['role:admin']], function () {
        Route::post('cancel-bonus', '\Klsandbox\BonusRoute\Http\Controllers\BonusManagementController@postCancelBonus');
        Route::post('set-approvals-all', '\Klsandbox\BonusRoute\Http\Controllers\BonusManagementController@postSetApprovalsAll');
        Route::get('set-payments-approvals', '\Klsandbox\BonusRoute\Http\Controllers\BonusManagementController@getSetPaymentsApprovals');
        Route::get('excel/{monthly_report_id}/{type}', '\Klsandbox\BonusRoute\Http\Controllers\BonusManagementController@getExcel');
        Route::get('txt/{monthly_report_id}/{type}', '\Klsandbox\BonusRoute\Http\Controllers\BonusManagementController@getExport');
        Route::get('list-payments/{year}/{month}/{filter}', '\Klsandbox\BonusRoute\Http\Controllers\BonusManagementController@getListPayments');
        Route::get('bonus-payments-list', '\Klsandbox\BonusRoute\Http\Controllers\BonusManagementController@getBonusPaymentsList');


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
