<?php

Route::group(['prefix' => 'bonus-management', 'middleware' => 'config:has_bonus'], function () {
    Route::group(['middleware' => ['auth']], function () {
        Route::get('choose-payout/{bonus_id}/{bonus_payout_id}', '\Klsandbox\BonusRoute\Http\Controllers\BonusManagementController@getChoosePayout');
        Route::get('view/{bonus_id}', '\Klsandbox\BonusRoute\Http\Controllers\BonusManagementController@getView');
        Route::get('list/{filter}', '\Klsandbox\BonusRoute\Http\Controllers\BonusManagementController@getList');
    });

    Route::group(['middleware' => ['role:admin']], function () {
        Route::post('cancel-bonus', '\Klsandbox\BonusRoute\Http\Controllers\BonusManagementController@postCancelBonus');
        Route::post('set-approvals-all', '\Klsandbox\BonusRoute\Http\Controllers\BonusManagementController@postSetApprovalsAll');
        Route::get('set-payments-approvals', '\Klsandbox\BonusRoute\Http\Controllers\BonusManagementController@getSetPaymentsApprovals');
        Route::get('excel/{monthly_report_id}/{type}', '\Klsandbox\BonusRoute\Http\Controllers\BonusManagementController@getExcel');
        Route::get('txt/{monthly_report_id}/{type}', '\Klsandbox\BonusRoute\Http\Controllers\BonusManagementController@getExport');
        Route::get('list-payments/{year}/{month}/{filter}', '\Klsandbox\BonusRoute\Http\Controllers\BonusManagementController@getListPayments');
        Route::get('bonus-payments-list', '\Klsandbox\BonusRoute\Http\Controllers\BonusManagementController@getBonusPaymentsList');
    });
});
