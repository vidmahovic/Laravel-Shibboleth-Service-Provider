<?php namespace StudentAffairsUwm\Shibboleth\Controllers;

use Illuminate\Auth\GenericUser;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\View;
use JWTAuth;
use Request;
use App\Services\UserServiceTestFed as UserServiceTestFed;
use DB;
use App\Services\ServerService as ServerService;

class ShibbolethController extends Controller
{
    // TODO: Can we get rid of this and get it more dynamically?
    private $ctrpath = "\StudentAffairsUwm\\Shibboleth\\Controllers\\ShibbolethController@";

    /**
     * Service Provider
     * @var Shibalike\SP
     */
    private $sp;

    /**
     * Identity Provider
     * @var Shibalike\IdP
     */
    private $idp;

    /**
     * Configuration
     * @var Shibalike\Config
     */
    private $config;

    /**
     * Constructor
     */
    public function __construct(GenericUser $user = null)
    {

        //$this->middleware('auth');


        if (config('shibboleth.emulate_idp') == true) {
            $this->config         = new \Shibalike\Config();
            $this->config->idpUrl = 'idp';

            $stateManager = $this->getStateManager();

            $this->sp = new \Shibalike\SP($stateManager, $this->config);
            $this->sp->initLazySession();

            $this->idp = new \Shibalike\IdP($stateManager, $this->getAttrStore(), $this->config);
        }

        $this->user = $user;
    }


    /**
     * Setup authorization based on returned server variables
     * from the IdP.
     * POPRAVI TAKO, DA BO UPDATE-AL PRAVO TABELO (TOREJ MYSQL TABELO)
     */
    public function idpAuthorize()
    {

        $userid = ServerService::parseXML(ServerService::getShibbolethVariable(config('shibboleth.idp_login_id')));
        $email = ServerService::getShibbolethVariable(config('shibboleth.idp_login_email'));
        $given_name = ServerService::getShibbolethVariable(config('shibboleth.idp_login_given_name'));
        $common_name = ServerService::getShibbolethVariable(config('shibboleth.idp_login_common_name'));
        $surname = ServerService::getShibbolethVariable(config('shibboleth.idp_login_last'));
        $primary_affiliation = ServerService::getShibbolethVariable(config('shibboleth.idp_login_pr_affiliation'));
        $principal_name = ServerService::getShibbolethVariable(config('shibboleth.idp_login_pr_name'));
        $home_org = ServerService::getShibbolethVariable(config('shibboleth.idp_login_home_org'));
        $home_org_type = ServerService::getShibbolethVariable(config('shibboleth.idp_login_home_org_type'));
        $shib_session_id = ServerService::getShibbolethVariable("Shib-Session-ID");

        if(UserServiceTestFed::matchingCredentials($primary_affiliation)) {

            $user = new UserServiceTestFed($userid, $common_name, $surname, $given_name, 
                $email, $primary_affiliation, $principal_name, $home_org, $home_org_type);

            if(Auth::attempt(['id' => $userid, 'primary_affiliation' => $primary_affiliation])){
                Auth::attempt(['id' => $userid, 'primary_affiliation' => $primary_affiliation]);
                //Auth::login($user);
                $user->createOrUpdateUser("update");
                $user->createSession($shib_session_id);
                return Redirect::to(config('shibboleth.shibboleth_authenticated'));

            } else {
                $user->createOrUpdateUser("create");
                $user->createSession($shib_session_id);
                if(Auth::attempt(['id' => $userid, 'primary_affiliation' => $primary_affiliation])) {
                    Auth::attempt(['id' => $userid, 'primary_affiliation' => $primary_affiliation]);
                    return Redirect::to(config('shibboleth.shibboleth_authenticated'));
                } else {
                    return Redirect::to(config('shibboleth.shibboleth_unauthorized'));
                }
            }
        } else {
            return Redirect::to(config('shibboleth.shibboleth_unauthorized'));
        }
    }



    /**
     * Destroy the current session and log the user out, redirect them to the main route.
     */
    public function destroy()
    {
        Auth::logout();
        Session::flush();

        return Redirect::to('https://' . Request::server('SERVER_NAME') . config('shibboleth.idp_logout'));
    }

}

?>
