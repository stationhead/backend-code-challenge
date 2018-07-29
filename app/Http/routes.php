<?php

Route::post('playTracks', ['uses' => 'playController@store', 'as' => 'playController.store']);
