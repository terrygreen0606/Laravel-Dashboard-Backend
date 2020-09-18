<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
add_cors_headers_group_cdt();

Route::group([
    'namespace' => 'Auth',
    'middleware' => 'api',
    'prefix' => 'password'
], function () {
    Route::post('create', 'PasswordResetController@create');
//    Route::get('find/{token}', 'PasswordResetController@find');
//    Route::post('reset', 'PasswordResetController@reset');
    Route::post('reset/{token}', 'PasswordResetController@findAndReset');
});

Route::group([

    'middleware' => 'api',
    'prefix' => 'auth'

], function ($router) {

    Route::post('login', 'AuthController@login');
    Route::post('logout', 'AuthController@logout');
    Route::post('refresh', 'AuthController@refresh');
    Route::post('me', 'AuthController@me');

});


Route::group([

   'middleware' => ['jwt.auth'],

], function ($router)
{

    /* Dashboard */
    Route::get('/dashboard','DashboardController@home');

    Route::post('users/create', 'UserController@store')->middleware('permission:users,C');
    Route::post('users/upload/bulkCsv', 'UserController@storeCsv')->middleware('permission:users,C');

    Route::post('users/assignMultipleUsers', 'UserController@assignMultipleUsers')->middleware('permission:users,U');
    Route::delete('users/{id}/destroy', 'UserController@destroy')->middleware('permission:users,D');
    Route::get('users/{id}', 'UserController@show')->middleware('permission:users,R');
    Route::post('users/{id}', 'UserController@update')->middleware('permission:users,U');
    Route::post('changePassword', 'UserController@changePassword')->middleware('permission:users,U');
    Route::post('users/{id}/quickUpdate', 'UserController@quickUpdate')->middleware('permission:users,U');
    Route::post('users/{id}/resumeUpdate', 'UserController@resumeUpdate')->middleware('permission:users,U');
    Route::post('users/{user}/toggleStatus', 'UserController@toggleStatus')->middleware('permission:users,U');
    Route::post('users', 'UserController@getAll')->middleware('permission:users,R');
    Route::get('getAllUsersName', 'UserController@getAllUsersName')->middleware('permission:users,R');
    Route::get('users/duplicateEmail/{email}', 'UserController@getDuplicateEmail')->middleware('permission:users,R');
    Route::post('checkUserName', 'UserController@getUserName')->middleware('permission:users,R');
    Route::post('users-filter', 'UserController@getBySearch')->middleware('permission:users,R');
    Route::post('users-order', 'UserController@getByOrder')->middleware('permission:users,R');
    Route::get('users/{id}/short', 'UserController@indexShortId')->middleware('permission:users,R');
    Route::get('users/company/{id}/short', 'UserController@indexShortByCompany')->middleware('permission:users,R');
    Route::post('users/{id}/smff/create', 'UserController@storeSMFF')->middleware('permission:users,U');
    Route::get('users/{id}/smff', 'UserController@showSMFF')->middleware('permission:users,R');
    Route::post('users/{id}/smff', 'UserController@updateSMFF')->middleware('permission:users,U');
    Route::post('users/{id}/network', 'UserController@updateNetwork')->middleware('permission:users,U');
    Route::delete('users/{id}/smff', 'UserController@destroySMFF')->middleware('permission:users,D');
    Route::get('users/{user}/notes', 'UserController@getNotes')->middleware('permission:users,R');
    Route::post('users/{user}/addNote', 'UserController@addNote')->middleware('permission:users,C');
    Route::delete('users/{user}/notes/{id}/destroy', 'UserController@destroyNote')->middleware('permission:users,D');
    Route::get('users/{user}/files/documents/{type}', 'UserController@getFiles')->middleware('permission:users,R');
    Route::post('users/{user}/files/documents/{type}/upload', 'UserController@uploadFile')->middleware('permission:users,U');
    Route::post('users/{user}/files/documents/{type}/bulkdestroy', 'UserController@bulkDestroyFile')->middleware('permission:users,D');
    Route::delete('users/{user}/files/documents/{type}/{fileName}/destroy', 'UserController@destroyFile')->middleware('permission:users,D');
    Route::get('users/{user}/files/documents/{type}/{fileName}/download', 'UserController@downloadFileForce')->middleware('permission:users,R');

    Route::get('users/{user}/addresses', 'UserAddressesController@index')->middleware('permission:users,R');
    Route::post('users/{user}/addresses/store', 'UserAddressesController@store')->middleware('permission:users,C');
    Route::post('users/addresses/{user}', 'UserAddressesController@updateAddress')->middleware('permission:users,U');
    Route::delete('users/addresses/{address}', 'UserAddressesController@destroyAddress')->middleware('permission:users,D');

    Route::post('users/{user}/setPhoto', 'UserController@storePhoto')->middleware('permission:users,U');
    Route::get('users/{user}/getPhoto', 'UserController@getPhoto')->middleware('permission:users,R');
    Route::delete('users/{user}/destroyPhoto', 'UserController@destroyPhoto')->middleware('permission:users,D');
    Route::post('users/{user}/passwordResetEmail', 'UserController@sendPasswordResetEmail')->middleware('permission:users,U');

    Route::get('companies/user/poc', 'UserController@getPOCs')->middleware('permission:companies,R'); //point of contacts are read by the company

    Route::get('roles', 'RoleController@index')->middleware('permission:users,R');
    Route::get('contacttypes', 'UserController@contactTypes')->middleware('permission:users,R');
    Route::get('companies/opaNetwork', 'CompanyController@getOpaNetwork')->middleware('permission:companies,R');
    Route::get('companies/opaNetwork/{id}', 'CompanyController@getOpaNetworkCompanyCodes')->middleware('permission:companies,R');
    Route::post('companies/quickUpdateCompany', 'CompanyController@quickUpdateCompany')->middleware('permission:companies,U');
    Route::post('companies/assignMultipleCompany', 'CompanyController@assignMultipleCompany')->middleware('permission:companies,U');
    Route::post('companies/quickUpdateVessel', 'CompanyController@quickUpdateVessel')->middleware('permission:companies,U');
    Route::get('companies', 'CompanyController@index')->middleware('permission:companies,R');
    Route::get('companies/planpreparer', 'CompanyController@getPlanPreparer')->middleware('permission:companies,R');
    Route::get('companies/{id}/unlinkCompanies', 'CompanyController@unlinkOperatingCompany')->middleware('permission:companies,R');
    Route::get('companies/{id}/unlinkCompanies', 'CompanyController@unlinkOperatingCompany')->middleware('permission:companies,R');
    Route::get('companies/{id}/unlinkUsers', 'CompanyController@unlinkIndividual')->middleware('permission:companies,R');
    Route::get('companies/{id}/unlinkVessels', 'CompanyController@unlinkVessel')->middleware('permission:companies,R');
    Route::post('companies/create', 'CompanyController@store')->middleware('permission:companies,C');
    Route::get('companies/short', 'CompanyController@indexShort')->middleware('permission:companies,R');
    Route::get('companies/duplicatePlan/{planNumber}', 'CompanyController@getDuplicatePlanNumber')->middleware('permission:companies,R');
    Route::get('companies/duplicateEmail/{email}', 'CompanyController@getDuplicateCompanyEmail')->middleware('permission:companies,R');
    Route::get('companies/{id}', 'CompanyController@show')->middleware('permission:companies,R');
    Route::post('companies/{id}', 'CompanyController@update')->middleware('permission:companies,U');
    Route::post('companies/network/{id}', 'CompanyController@saveOpaNetwork')->middleware('permission:companies,U');
    Route::delete('companies/{id}/destroy', 'CompanyController@destroy')->middleware('permission:companies,D');
    Route::get('companies/{id}/short', 'CompanyController@getShort')->middleware('permission:companies,R');
    Route::get('companies/{id}/shortWithAddress', 'CompanyController@getShortWithAddress')->middleware('permission:companies,R');
    Route::get('companies/{company}/contacts/primary', 'CompanyController@getPrimaryContacts')->middleware('permission:companies,R');
    Route::get('companies/{company}/contacts/secondary', 'CompanyController@getSecondaryContacts')->middleware('permission:companies,R');
    Route::get('companies/{company}/contacts', 'CompanyController@getContacts')->middleware('permission:companies,R');
    Route::get('companies/{company}/qi', 'CompanyController@getQI')->middleware('permission:companies,R');
    Route::get('companies/{company}/contacts/dpa', 'CompanyController@getContactsDPA')->middleware('permission:companies,R');
    Route::post('companies/{company}/toggleStatus', 'CompanyController@toggleStatus')->middleware('permission:companies,U');
    Route::post('companies/{company}/toggleVendor', 'CompanyController@toggleVendor')->middleware('permission:companies,U');
    Route::post('companies/{company}/toggleNetworks', 'CompanyController@toggleNetworks')->middleware('permission:companies,U');
    Route::post('companies', 'CompanyController@getAll')->middleware('permission:companies,R');
    Route::post('companies-filter', 'CompanyController@getBySearch')->middleware('permission:companies,R');
    Route::post('companies-filter-vrp', 'CompanyController@getBySearchWithVRP')->middleware('permission:companies,R');
    Route::post('companies-order', 'CompanyController@getByOrder')->middleware('permission:companies,R');
    Route::delete('companies/contacts/{id}/destroy', 'CompanyController@destroyContact')->middleware('permission:companies,D');
    Route::get('companies/contacts/types', 'CompanyController@getContactTypes')->middleware('permission:companies,R');
    Route::post('companies/{id}/contacts/create', 'CompanyController@storeContact')->middleware('permission:companies,C');
    Route::post('companies/{id}/contacts/save', 'CompanyController@updateContact')->middleware('permission:companies,U');
    Route::get('companies/{company}/documents/count', 'CompanyController@getFilesCount')->middleware('permission:companies,R');
    Route::get('companies/{company}/documents/{type}', 'CompanyController@getFilesDOC')->middleware('permission:companies,R');
    Route::post('companies/{company}/documents/{type}/upload', 'CompanyController@uploadFileDOC')->middleware('permission:companies,U');
    Route::post('companies/{company}/documents/{type}/generate', 'CompanyController@generateFileDOC')->middleware('permission:companies,U');//DEBUG->GET
    Route::post('companies/{company}/documents/{type}/{location}/generateV2', 'CompanyDocController@generate')->middleware('permission:companies,U');
    Route::post('companies/{company}/documents/{type}/bulkdestroy', 'CompanyController@bulkDestroy')->middleware('permission:companies,D'); // destroy files
    Route::delete('companies/{company}/documents/{type}/{fileName}/destroy', 'CompanyController@destroyFileDOC')->middleware('permission:companies,D');
    Route::get('companies/{company}/documents/{type}/{fileName}/download', 'CompanyController@downloadFileDOCForce')->middleware('permission:companies,R');
    Route::post('companies/{id}/smff/create', 'CompanyController@storeSMFF')->middleware('permission:companies,C');
    Route::get('companies/{id}/smff', 'CompanyController@showSMFF')->middleware('permission:companies,R');
    Route::post('companies/{id}/smff', 'CompanyController@updateSMFF')->middleware('permission:companies,U');
    Route::post('companies/{id}/network', 'CompanyController@updateNetwork')->middleware('permission:companies,U');
    Route::delete('companies/{id}/smff', 'CompanyController@destroySMFF')->middleware('permission:companies,D');
    Route::get('companies/VRPexpress/{plan}', 'CompanyController@getVRPdata')->middleware('permission:companies,R');
    Route::post('companies/{id}/import', 'CompanyController@importVrp')->middleware('permission:companies,R');
   // Route::get('companies/vendors', 'CompanyController@importVendors')->middleware('permission:companies,R');

    //Route::get('import-once', 'CompanyController@importVendors')->middleware('permission:companies,R');
    //Route::post('companies/vendor-type', 'CompanyController@updateVendorType')->middleware('permission:companies,U');
    Route::post('vendor/type', 'CompanyController@updateVendorType')->middleware('permission:companies,U');

    Route::get('companies/{company}/addresses', 'CompanyAddressesController@index')->middleware('permission:companies,R');
    Route::post('companies/{company}/addresses/store', 'CompanyAddressesController@store')->middleware('permission:companies,C');
    Route::post('companies/addresses/{address}', 'CompanyAddressesController@updateAddress')->middleware('permission:companies,U');
    Route::delete('companies/addresses/{address}', 'CompanyAddressesController@destroyAddress')->middleware('permission:companies,D');

    Route::post('companies/{company}/setPhoto', 'CompanyController@storePhoto')->middleware('permission:companies,U');
    Route::get('companies/{company}/getPhoto', 'CompanyController@getPhoto')->middleware('permission:companies,R');
    Route::delete('companies/{company}/destroyPhoto', 'CompanyController@destroyPhoto')->middleware('permission:companies,D');

    Route::get('companies/{company}/notes', 'CompanyController@getNotes')->middleware('permission:companies,R');
    Route::post('companies/{company}/addNote', 'CompanyController@addNote')->middleware('permission:companies,C');
    Route::delete('companies/{company}/notes/{id}/destroy', 'CompanyController@destroyNote')->middleware('permission:companies,D');

    // get company id, name and add bulk csv
    Route::get('company/getCompanyInfo', 'CompanyController@getCompanyInfo')->middleware('permission:companies,R');
    Route::post('companies/upload/bulkCsv', 'CompanyController@storeBulk')->middleware('permission:companies,C');

    Route::get('address/types', 'AddressController@types');
    Route::get('address/countries', 'AddressController@countries');

    Route::get('vendors', 'VendorController@index')->middleware('permission:vendors,R');
    Route::get('vendors/types', 'VendorTypeController@index')->middleware('permission:vendors,R');
    Route::get('vendors/qi', 'VendorController@getQI')->middleware('permission:vendors,R');
    Route::get('vendors/pi', 'VendorController@getPI')->middleware('permission:vendors,R');
    Route::get('vendors/response', 'VendorController@getResponse')->middleware('permission:vendors,R');
    Route::get('vendors/societies', 'VendorController@getSocieties')->middleware('permission:vendors,R');
    Route::get('vendors/insurers', 'VendorController@getInsurers')->middleware('permission:vendors,R');
    Route::get('vendors/providers', 'VendorController@getProviders')->middleware('permission:vendors,R');
    Route::post('vendors', 'VendorController@getAll')->middleware('permission:vendors,R');
    Route::get('vendors/{id}', 'VendorController@show')->middleware('permission:vendors,R');
    Route::post('vendors/{id}', 'VendorController@update')->middleware('permission:vendors,U');
    Route::post('vendors-filter', 'VendorController@getBySearch')->middleware('permission:vendors,R');
    Route::post('vendors-order', 'VendorController@getByOrder')->middleware('permission:vendors,R');
    Route::delete('vendors/{id}/destroy', 'VendorController@destroy')->middleware('permission:vendors,D');
    Route::post('vendors/create', 'VendorController@store')->middleware('permission:vendors,C');



    Route::post('vessels', 'VesselController@getAll')->middleware('permission:vessels,R');
    Route::post('vessels/assignMultipleVessel', 'VesselController@assignMultipleVessel')->middleware('permission:vessels,U');
    Route::post('vessels/short/index', 'VesselController@getAllShort')->middleware('permission:vessels,R');
    Route::get('vessels/company/{cid}/index', 'VesselController@getAllUnderCompanyShort')->middleware('permission:vessels,R');
    Route::get('vessels/unAssignedVessel', 'VesselController@unAssignedVessel')->middleware('permission:vessels,R');
    Route::get('vessels/imoVessels/{number}/{flag}', 'VesselController@getDuplicateIMOVessel')->middleware('permission:vessels,R');
    Route::get('vessels/related/list', 'VesselController@getRelatedList')->middleware('permission:vessels,R');

    // Get vessel id, name
    Route::get('vessels/getVesselInfo', 'VesselController@getVesselInfo')->middleware('permission:vessels,R');
    Route::post('vessels/latest-ais-positions', 'VesselController@getLatestAISPositions')->middleware('permission:vessels,R');

    Route::get('vessels/lead/list', 'VesselController@getParentList')->middleware('permission:vessels,R');

    Route::get('vessels/parent/list', 'VesselController@getParentList')->middleware('permission:vessels,R');
    Route::get('vessels/lead/sister/list', 'VesselController@getSisterList')->middleware('permission:vessels,R');
    Route::get('vessels/sister/list', 'VesselController@getSisterVesselsList')->middleware('permission:vessels,R');
    Route::get('vessels/child/list', 'VesselController@getChildVesselsList')->middleware('permission:vessels,R');
    Route::post('vessels-filter', 'VesselController@getBySearch')->middleware('permission:vessels,R');
    Route::post('vessels-filter-vrp', 'VesselController@getBySearchWithVRP')->middleware('permission:vessels,R');
    Route::post('vessels-order', 'VesselController@getByOrder')->middleware('permission:vessels,R');
    Route::get('vessels/types', 'VesselTypeController@index')->middleware('permission:vessels,R');
    Route::post('vessels/{vessel}/toggleStatus', 'VesselController@toggleStatus')->middleware('permission:vessels,U');
    Route::post('vessels/{vessel}/toggleTanker', 'VesselController@toggleTanker')->middleware('permission:vessels,U');
    Route::delete('vessels/{id}/destroy', 'VesselController@destroy')->middleware('permission:vessels,D');
    Route::post('vessels/create', 'VesselController@store')->middleware('permission:vessels,C');

    Route::post('vessels/upload/bulkCsv', 'VesselController@storeCSV')->middleware('permission:vessels,C');
    Route::post('vessels/upload/bulkRelations', 'VesselController@sisterVesselImport')->middleware('permission:vessels,C');
    Route::post('vessels/upload/capabilitiesimport', 'VesselController@capabilitiesImport')->middleware('permission:vessels,C');

    Route::get('vessels/{id}', 'VesselController@show')->middleware('permission:vessels,R');
    Route::post('vessels/{id}', 'VesselController@update')->middleware('permission:vessels,U');
    Route::post('vessels/{id}/updateDimensions', 'VesselController@updateDimensions')->middleware('permission:vessels,U');
    Route::post('vessels/{id}/updateProviders', 'VesselController@updateProviders')->middleware('permission:vessels,U');
    Route::get('vessels/{id}/constructionDetail', 'VesselController@showConstructionDetail')->middleware('permission:vessels,R');
    Route::post('vessels/{id}/constructionDetail', 'VesselController@updateConstructionDetail')->middleware('permission:vessels,U');
    Route::post('vessels/{id}/updateRelation', 'VesselController@updateRelation')->middleware('permission:vessels,U');
    Route::post('vessels/{id}/makeLead', 'VesselController@makeLead')->middleware('permission:vessels,U');
    Route::post('vessels/relation/remove', 'VesselController@removeRelation')->middleware('permission:vessels,U');
    Route::get('vessels/{id}/ais', 'VesselController@showAIS')->middleware('permission:vessels,R');
    Route::get('vessels/{id}/vrp', 'VesselController@showVRP')->middleware('permission:vessels,R');
    Route::post('vessels/{id}/import', 'VesselController@importVrp')->middleware('permission:vessels,R');
    Route::post('vessels/{id}/ais', 'VesselController@updateAIS')->middleware('permission:vessels,U');
    Route::post('vessels/{id}/smff/create', 'VesselController@storeSMFF')->middleware('permission:vessels,C');
    Route::get('vessels/{id}/smff', 'VesselController@showSMFF')->middleware('permission:vessels,R');
    Route::post('vessels/{id}/smff', 'VesselController@updateSMFF')->middleware('permission:vessels,U');
    Route::post('vessels/{id}/network', 'VesselController@updateNetwork')->middleware('permission:vessels,U');
    Route::delete('vessels/{id}/smff', 'VesselController@destroySMFF')->middleware('permission:vessels,D');
    Route::get('vessels/{id}/short', 'VesselController@indexShort')->middleware('permission:vessels,R');
    Route::get('vessels/{vessel}/notes', 'VesselController@getNotes')->middleware('permission:vessels,R');
    Route::post('vessels/{vessel}/addNote', 'VesselController@addNote')->middleware('permission:vessels,C');
    Route::delete('vessels/{vessel}/notes/{id}/destroy', 'VesselController@destroyNote')->middleware('permission:vessels,D');

    Route::get('vessels/{vessel}/files/{location}/{year}', 'VesselController@getUploadedFiles')->middleware('permission:vessels,R');
    Route::post('vessels/{vessel}/files/{location}/{year}/upload', 'VesselController@uploadVesselFiles')->middleware('permission:vessels,U');
    Route::delete('vessels/{vessel}/files/{location}/{year}/{fileName}/destroy', 'VesselController@deleteSingleVesselFile')->middleware('permission:vessels,D');
    Route::post('vessels/{vessel}/files/{location}/{year}/deleteAll', 'VesselController@deleteAllVesselFiles')->middleware('permission:vessels,D'); //bulk destroy files in vessels
    Route::get('vessels/{vessel}/files/{location}/{year}/{fileName}/download', 'VesselController@downloadVesselFile')->middleware('permission:vessels,R');

    Route::get('vessels/VRPexpress/{id}', 'VesselController@getVRPdata')->middleware('permission:companies,R');
    Route::get('vessels/VRPexpress/underPlan/{company}', 'VesselController@getVesselsUnderPlan')->middleware('permission:vessels,R');
    Route::post('vessels/company/transfer', 'VesselController@transferToCompany')->middleware('permission:vessels,U');

    Route::post('vessels/{vessel}/setPhoto', 'VesselController@storePhoto')->middleware('permission:vessels,U');
    Route::get('vessels/{vessel}/getPhoto', 'VesselController@getPhoto')->middleware('permission:vessels,R');
    Route::delete('vessels/{vessel}/destroyPhoto', 'VesselController@destroyPhoto')->middleware('permission:vessels,D');

    Route::post('vessels/{vessel}/saveFleets', 'VesselController@saveFleets')->middleware('permission:vessels,U');
    Route::post('vessels/bulk/actionInitiate', 'VesselController@bulkAction')->middleware('permission:vessels,U');

    Route::post('vessels/uploaded/countFiles', 'VesselController@getFilesCount')->middleware('permission:vessels,R');

    // New AIS Controller
    Route::get('ais/{id}/showdata', 'AISMTController@showAISData')->middleware('permission:vessels,R');
    Route::post('ais/getaisposition', 'AISMTController@getAIS_PS07_Single')->middleware('permission:vessels,C');
    Route::post('ais/getaispositioncont/{id}/{satellite}/{condition}', 'AISMTController@getAIS_PS07_Cont')->middleware('permission:vessels,C');
    Route::post('ais/getaisbulkposition', 'AISMTController@getAIS_PS07_Bulk')->middleware('permission:vessels,C');
    Route::post('ais/getaisbulkpositioncont', 'AISMTController@getAIS_PS07_Bulk_Cont')->middleware('permission:vessels,C');
    Route::post('ais/getcostaispoll', 'AISMTController@costVesselsAISPoll')->middleware('permission:vessels,C');
    Route::get('ais/getaissettings', 'AISMTController@getSettings')->middleware('permission:vessels,R');
    Route::post('ais/getaissettings', 'AISMTController@saveSettings')->middleware('permission:vessels,C');
    Route::post('ais/getaishistoricaltrack', 'AISMTController@getAIS_PS01_Single')->middleware('permission:vessels,C');
    Route::post('ais/getaisphoto/{imo}', 'AISMTController@getAIS_VD01_Single')->middleware('permission:vessels,C');
    Route::post('ais/getparticular/{id}', 'AISMTController@getAIS_VD02_Single')->middleware('permission:vessels,C');
    Route::post('ais/getcredit', 'AISMTController@checkingCredit')->middleware('permission:vessels,C');
    // End New AIS Controller

    Route::post('ais/getaisdata/{imo}', 'AISController@getAISData')->middleware('permission:vessels,C');
    Route::post('ais/getaisparticulars/{imo}', 'AISController@getAISParticulars')->middleware('permission:vessels,C');
    Route::post('ais/getaistrack/{imo}', 'AISController@getAISTrack')->middleware('permission:vessels,C');
    Route::post('ais/addaispoll', 'AISController@addVesselsAISPoll')->middleware('permission:vessels,C');
    Route::post('ais/costaispoll', 'AISController@costVesselsAISPoll')->middleware('permission:vessels,C');


    Route::get('ais/{vessel}/getTrack', 'AISController@getTrackData')->middleware('permission:vessels,R');
    Route::get('ais/vessels/{vessel}/getaispoll', 'AISController@getVesselsAISPoll')->middleware('permission:vessels,R');
    Route::get('ais/networks/{network}/getaispoll', 'AISController@getNetworkAISPoll')->middleware('permission:vessels,R');
    Route::get('ais/fleets/{fleet}/getaispoll', 'AISController@getFleetAISPoll')->middleware('permission:vessels,R');

    Route::get('ais/vessels/{vessel}/getaistype/{type}', 'AISController@getVesselsAISPollByType')->middleware('permission:vessels,R');
    Route::get('ais/networks/{network}/getaistype/{type}', 'AISController@getNetworkAISPollByType')->middleware('permission:vessels,R');
    Route::get('ais/fleets/{fleet}/getaistype/{type}', 'AISController@getFleetAISPollByType')->middleware('permission:vessels,R');

    Route::get('ais/stopaispoll/{id}', 'AISController@stopAISPoll')->middleware('permission:vessels,C');

    Route::get('ais/vessels', 'AISMTController@getAllVesselsWithPolls')->middleware('permission:vessels,C');

    Route::get('ais/settings', 'AISController@getSettings')->middleware('permission:vessels,C');

    Route::post('ais/settings', 'AISController@saveSettings')->middleware('permission:vessels,C');

    Route::get('pdf/footer', 'PDFController@getFooter')->name('pdf.footer');

    Route::get('map/vessels/{filters}', 'MapController@getMapVessels')->middleware('permission:map,R');
    Route::get('map/vesseltrack/tooltip/{id}', 'MapController@getMapVesselTrackTooltipInfo')->middleware('permission:map,R');
    Route::get('map/vessel/{id}', 'MapController@getMapVessel')->middleware('permission:map,R');
    Route::get('map/fleets', 'MapController@getMapFleets')->middleware('permission:map,R');
    Route::get('map/capabilities', 'MapController@getCapabilitiesValues')->middleware('permission:map,R');
    Route::get('map/networks', 'MapController@getMapNetworks')->middleware('permission:map,R');
    Route::get('map/zones', 'MapController@getMapZones')->middleware('permission:map,R');
    Route::get('map/companies/{filters}', 'MapController@getMapCompanies')->middleware('permission:map,R');
    Route::get('map/individuals/{filters}', 'MapController@getMapIndividuals')->middleware('permission:map,R');
    Route::get('map/smff/{id}', 'MapController@getMapSMFF')->middleware('permission:map,R');
    Route::get('map/company/{id}', 'MapController@getMapCompany')->middleware('permission:map,R');
    Route::get('map/company/address/{id}', 'MapController@getMapCompanyAddress')->middleware('permission:map,R');
    Route::get('map/user/{id}', 'MapController@getMapUser')->middleware('permission:map,R');
    Route::get('map/user/address/{id}', 'MapController@getMapUserAddress')->middleware('permission:map,R');
    Route::get('map/search/{search}', 'MapController@searchMap')->middleware('permission:map,R');
    Route::get('map/zone-test-json', 'MapController@getZoneTestJson')->middleware('permission:map,R');
    // Route::get('map/export/CDT-Earth.kml', 'MapExportController@KMLEarth')->middleware('permission:map,R');
    Route::get('map/export/CDT.kml/{filters}', 'MapExportController@KML')->middleware('permission:map,R');
    Route::get('map/weather/{filter}', 'WeatherController@getWeather')->middleware('permission:map,R');

    Route::get('fleets', 'FleetController@index')->middleware('permission:fleets,R');
    Route::post('fleets', 'FleetController@getAll')->middleware('permission:fleets,R');
    Route::post('fleets-filter', 'FleetController@getBySearch')->middleware('permission:fleets,R');
    Route::post('fleets-order', 'FleetController@getByOrder')->middleware('permission:fleets,R');
    Route::post('fleets/create', 'FleetController@store')->middleware('permission:fleets,C');
    Route::post('fleets/addVessel', 'FleetController@addVesselToFleets')->middleware('permission:fleets,C');
    Route::delete('fleets/{id}/destroy', 'FleetController@destroy')->middleware('permission:fleets,D');
    Route::delete('fleets/{id}/{vessel}/destroyVessel', 'FleetController@destroyVesselFromFleet')->middleware('permission:fleets,D');
    Route::post('fleets/{id}', 'FleetController@update')->middleware('permission:fleets,U');
    Route::get('fleets/{id}', 'FleetController@show')->middleware('permission:fleets,R');

    Route::get('networks/short', 'NetworkController@indexShort');

    Route::get('zones/short', 'ZoneController@indexShort');

    Route::get('reports/nasa/potential', 'ReportsController@getNASAPotential')->middleware('permission:system_reports,R');
    Route::post('reports/donjon/vessels', 'ReportsController@getDJSVessels')->middleware('permission:system_reports,R');
    Route::post('reports/trackreport', 'ReportsController@trackReport')->middleware('permission:system_reports,R');
    Route::post('reports/activevessels', 'ReportsController@activeVesselReport')->middleware('permission:system_reports,R');
    Route::post('reports/nocontractcompany', 'ReportsController@noContractCompany')->middleware('permission:system_reports,R');
    Route::post('reports/schedule', 'ReportsController@setReportSchedule')->middleware('permission:system_reports,R');
    Route::get('reports/changedtables', 'ReportsController@changedTables')->middleware('permission:system_reports,R');
    Route::get('reports/actions', 'ReportsController@actions')->middleware('permission:system_reports,R');
    Route::get('reports/reporttype', 'ReportsController@reportType')->middleware('permission:system_reports,R');
    Route::get('reports/frequency', 'ReportsController@frequency')->middleware('permission:system_reports,R');
    Route::get('reports/exportactivevessel', 'ReportsController@exportActiveVessel')->middleware('permission:system_reports,R');
    Route::get('reports/exporttrackreport', 'ReportsController@exportTrackReport')->middleware('permission:system_reports,R');
    Route::get('reports/exportnocontractcompany', 'ReportsController@exportNoContractCompany')->middleware('permission:system_reports,R');

    Route::get('widgets/heartbeat', 'StatsController@systemHeartBeat');
    Route::get('widgets/vessels', 'StatsController@activeVessels');
    Route::get('widgets/companies', 'StatsController@activeCompanies');
    Route::get('widgets/individuals', 'StatsController@activeIndividuals');

    Route::get('permissions/roles', 'PermissionsController@getRoles')->middleware('permission:settings,R');
    Route::get('permissions/components', 'PermissionsController@getComponents')->middleware('permission:settings,R');
    Route::get('permissions/components/{roleId}', 'PermissionsController@getComponentsByRole')->middleware('permission:settings,R');
    Route::post('permissions/update', 'PermissionsController@updatePermissions')->middleware('permission:settings,U');


    // simple alert system
    Route::get('alerts', 'AlertController@getAlerts')->middleware('permission:alert,R');
    Route::post('alert/add-new', 'AlertController@addNew')->middleware('permission:alert,C');
    Route::post('alert/update/{id}', 'AlertController@updateAlert')->middleware('permission:alert,U');
    Route::post('alert/active/{id}', 'AlertController@activeAlert')->middleware('permission:alert,U');
    Route::delete('alert/delete/{id}', 'AlertController@deleteAlert')->middleware('permission:alert,D');
    Route::post('alert/dashboard', 'AlertController@dashboardAlerts')->middleware('permission:alert,R');
    Route::post('alert/menubar', 'AlertController@menubarAlerts')->middleware('permission:alert,R');

    //Piers
    Route::get('map/piers/{id}', 'PierController@show')->middleware('permission:users,R');
    Route::get('map/piers', 'PierController@getAll')->middleware('permission:users,R');
    // Route::get('imosingle', 'ScrapeMTbyIMOSingle@getIMOData')->middleware('permission:imosingle,R');

    // Weather
    Route::post('weather/test', 'WeatherController@test');
});
//Route::get('debug', 'CompanyController@getAllDebug');
//Route::get('bulk', 'VesselController@bulkImport');
//Route::get('/test/permissions', 'RoleController@index')->middleware('permission:users,C,U');
