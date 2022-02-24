<?php

namespace App\Http\Controllers;

use App\Customer;
use App\DistributorDoctor;
use App\DoctorPrescriptionDetail;
use App\DoctorPrescriptionMaster;
use App\Invoice;
use App\InvoiceDetail;
use App\Setting;
use App\UnMapDoctor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

/**
 * Class SettingsController
 * @package App\Http\Controllers
 */
class SettingsController extends Controller {

	private $dataErrors = [];

	public function __construct() {

	}

	/**
	 * @return \Illuminate\Contracts\View\Factory|\Illuminate\Http\RedirectResponse|\Illuminate\View\View
	 */
	public function index() {

		$data['days'] = Setting::retriveSettingsData( 'days' ) ?: '';

		return view( 'settings.index', $data );
	}

	public function setBookingDays( Request $request ) {

		$validatedData = $this->validate( $request, [
			'days' => 'required|integer'
		] );

		$this->storeSettingsData( $validatedData );

		return redirect()->back();
	}

	public function storeSettingsData( $items ) {
		try {

			if ( ! is_array( $items ) ) {
				new throwException();
			}

			foreach ( $items as $key => $value ) {
				Setting::updateOrCreate( [ 'key' => $key ], [ 'value' => $value ] );
			}

			Session::flash( 'success', 'Saved Successfully' );

//            fileCache()->forever('cachedExcludedProductsDuringDataUpload', Setting::retriveSettingsData('excludedProductsDuringDataUpload'));


		} catch ( \Exception $e ) {

			Session::flash( 'error', 'Can\'t update data now' );

		}

		return;

	}
}
