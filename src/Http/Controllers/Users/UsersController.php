<?php

namespace Joselfonseca\LaravelAdmin\Http\Controllers\Users;

use DB;
use Auth;
use Datatables;
use Prettus\Validator\Exceptions\ValidatorException;
use SweetAlert;
use Illuminate\Http\Request;
use Joselfonseca\LaravelAdmin\Http\Requests;
use Joselfonseca\LaravelAdmin\Services\Acl\AclManager;
use Joselfonseca\LaravelAdmin\Http\Controllers\Controller;
use Joselfonseca\LaravelAdmin\Exceptions\EmailTakenException;
use Joselfonseca\LaravelAdmin\Contracts\UserRepositoryContract;

/**
 * Description of UsersController
 *
 * @author jfonseca
 */
class UsersController extends Controller
{

    /**
     * @var UserRepositoryContract
     */
    protected $repository;

    /**
     * UsersController constructor.
     * @param UserRepositoryContract $repository
     */
    public function __construct(UserRepositoryContract $repository)
    {
        $this->repository = $repository;
    }


    public function index(Request $request)
    {
        if ($request->ajax()) {
            return Datatables::of($this->repository->all(['id', 'name', 'email']))
                ->addColumn('action', function ($user) {
                    return '<a href="'.route('LaravelAdminUsersEdit', [$user->id]).'" class="btn btn-sm btn-primary"><i class="fa fa-pencil"></i> '.trans('laravel-admin.edit').'</a> &nbsp;&nbsp;<a href="'.route('deleteUser', [$user->id]).'" class="btn btn-sm btn-danger confirm-delete"><i class="fa fa-times"></i> '.trans('laravel-admin.delete').'</a>';
                })
                ->make();
        }
        return view('LaravelAdmin::users.index')->with('activeMenu', 'sidebar.users.list');
    }

    /**
     * @param AclManager $aclManager
     * @param $id
     * @return mixed
     */
    public function edit(AclManager $aclManager, $id)
    {
        $user = $this->repository->find($id);
        return view('LaravelAdmin::users.edit')
            ->with('user', $user)
            ->with('roles', $aclManager->getRolesForSelect())
            ->with('activeMenu', 'sidebar.users');
    }

    /**
     * @param AclManager $aclManager
     * @return mixed
     */
    public function create(AclManager $aclManager)
    {
        return view('LaravelAdmin::users.create')
            ->with('roles', $aclManager->getRolesForSelect())
            ->with('activeMenu', 'sidebar.users');
    }

    /**
     * @param Requests\CreateUserRequest $request
     * @return mixed
     */
    public function store(Requests\CreateUserRequest $request)
    {
        try{
            DB::transaction(function () use ($request) {
                $this->repository->create($request->all());
            });
        }catch (ValidatorException $e){
            return redirect()->back()->withErrors($e->getMessageBag())->withInput();
        }
        SweetAlert::success(trans('LaravelAdmin::laravel-admin.userCreated'));
        return redirect(config('laravel-admin.routePrefix', 'backend').'/users');
    }

    /**
     * @param Requests\UpdateUserRequest $request
     * @param $id
     * @return mixed
     */
    public function update(Requests\UpdateUserRequest $request, $id)
    {
        try {
            DB::transaction(function () use ($request, $id) {
                $this->repository->update($request->all(), $id);
            });
        } catch (EmailTakenException $e) {
            SweetAlert::error(trans('LaravelAdmin::laravel-admin.emailTaken'));
            return redirect()->back()->withInput();
        }
        SweetAlert::success(trans('LaravelAdmin::laravel-admin.userUpdated'));
        return redirect()->back();
    }

    /**
     * @param Requests\UpdatePasswordRequest $request
     * @param $id
     * @return mixed
     */
    public function updatePassword(Requests\UpdatePasswordRequest $request, $id)
    {
        DB::transaction(function () use ($request, $id) {
            $this->repository->updatePassword($id, $request->get('password'));
        });
        SweetAlert::success(trans('LaravelAdmin::laravel-admin.passwordUpdated'));
        return redirect()->back();
    }

    /**
     * @param $id
     * @return mixed
     */
    public function destroy($id)
    {
        $this->repository->delete($id);
        SweetAlert::success(trans('LaravelAdmin::laravel-admin.userDeleted'));
        return redirect()->back();
    }

    /**
     * @param AclManager $aclManager
     * @return mixed
     */
    public function me(AclManager $aclManager)
    {
        return view('LaravelAdmin::users.edit')
            ->with('user', Auth::user())
            ->with('roles', $aclManager->getRolesForSelect())
            ->with('activeMenu', 'sidebar.users');
    }

    /**
     * @param Requests\UpdateUserRequest $request
     * @return mixed
     */
    public function meEdit(Requests\UpdateUserRequest $request)
    {
        return $this->update($request, Auth::user()->id);
    }
}
