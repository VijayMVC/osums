<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\Redirect;
use App\Http\Controllers\Controller;
use App\Student;
use App\Department;
use Validator;
use Session;
use App\Registration;
use App\Transformers\StudentTransformer;


class studentController extends Controller {
	protected $student;
	protected $semesters=[
		'L1T1' => 'First Year 1st Semester',
		'L1T2' => 'First Year 2nd Semester',
		'L2T1' => 'Second Year 1st Semester',
		'L2T2' => 'Second Year 2nd Semester',
		'L3T1' => 'Third Year 1st Semester',
		'L3T2' => 'Third Year 2nd Semester'
	];
	public function __construct(Student $student)
	{
		$this->student = $student;
	}
	/**
	* Display  listing of the resource.
	*
	* @return Response
	*/

	public function index()
	{
		if(Session::has('deptId'))
		{
			$departments = Department::select('id','name')->orderby('name','asc')->lists('name', 'id');
			$selectDep=Session::get('deptId');
			$students =Student::where('department_id',$selectDep)->get();
			return view('student.index',compact('students','departments','selectDep'));
		}
		$departments = Department::select('id','name')->orderby('name','asc')->lists('name', 'id');
		$selectDep="";
		$students =array();
		return view('student.index',compact('students','departments','selectDep'));
	}
	public function index2(Request $request)
	{
		Session::put('deptId',$request->department_id);
		$departments = Department::select('id','name')->orderby('name','asc')->lists('name', 'id');
		$selectDep=$request->department_id;
		$students =Student::where('department_id',$selectDep)->get();

		return view('student.index',compact('students','departments','selectDep'));
	}

	public function studentList($dID,$session)
	{

		$students =Student::select('id','idNo','firstName','lastName','middleName')
		->where('department_id',$dID)
		->where('session',$session)
		->get();
		return Response()->json([
			'success' => true,
			'students' => $students
		], 200);

	}
	public function registeredStudentList($dID,$session,$semester)
	{

		$sdts=Registration::with(array('student' =>  function($query){
 					$query->select('id','idNo','firstName','middleName','lastName','photo');
 		}))
 		->where('department_id',$dID)
 		->where('session',$session)
 		->where('levelTerm',$semester)
 		->get();

		$students= Fractal()->collection($sdts, new StudentTransformer());
		return Response()->json([
			'success' => true,
			'students' => $students
		], 200);

	}



	/**
	* Show the form for creating a new resource.
	*
	* @return Response
	*/
	public function create()
	{
		$departments = Department::select('id','name')->orderby('name','asc')->lists('name', 'id');
		return view('student.create',compact('departments'));
	}


	/**
	* Store a newly created resource in storage.
	*
	* @return Response
	*/
	public function store(Request $request)
	{
		$data=$request->all();
		$rules=[
			'idNo' => 'required|unique:students',
			'session' => 'required',
			'department_id' => 'required',
			'bncReg' => 'required',
			'firstName' => 'required',
			'lastName' => 'required',
			'gender' => 'required',
			'religion' => 'required',
			'bloodgroup' => 'required',
			'nationality' => 'required',
			'dob' => 'required',
			'photo' => 'required|mimes:jpeg,jpg,png',
			'mobileNo' => 'required',
			'fatherName' => 'required',
			'fatherMobileNo' => 'required',
			'motherName' => 'required',
			'motherMobileNo' => 'required',
			'presentAddress' => 'required',
			'parmanentAddress' => 'required'
		];
		$validator = Validator::make($data, $rules);
		$errors=$validator->messages()->toArray();
		if ($validator->fails())
		{
			return Response()->json([
				'error' => true,
				'message' => $errors
			], 400);
		}

		$directory = public_path() . "/assets/images/students/";
		$fextention = $data['photo']->getClientOriginalExtension();
		$fileName=str_replace(' ','_',$data['idNo']).'.'.$fextention;
		$data['photo']->move($directory,$fileName);
		$data['photo']=$fileName;
		$student = new Student;
		$student->create($data);
		return Response()->json([
			'success' => true,
			'message' => "Student data store successfully."
		], 200);

	}


	/**
	* Display the specified resource.
	*
	* @param  int  $id
	* @return Response
	*/
	public function show($id)
	{
		try
		{

			$student = Student::with('department')->where('id',$id)->first();
			return view('student.show',compact('student'));
		}
		catch (Exception $e)
		{
			$notification= array('title' => 'Data Edit', 'body' => "There is no record.");
			return Redirect::route('student.index')->with("error",$notification);
		}
	}


	/**
	* Show the form for editing the specified resource.
	*
	* @param  int  $id
	* @return Response
	*/
	public function edit($id)
	{
		try
		{
			$departments =Department::select('id','name')->orderby('name','asc')->lists('name', 'id');
			$student = Student::findOrFail($id);
			return view('student.edit',compact('departments','student'));
		}
		catch (Exception $e)
		{
			$notification= array('title' => 'Data Edit', 'body' => "There is no record.");
			return Redirect::route('student.index')->with("error",$notification);
		}
	}


	/**
	* Update the specified resource in storage.
	*
	* @param  int  $id
	* @return Response
	*/
	public function update(Request $request,$id)
	{
		$data=$request->all();
		$rules=[
			'bncReg' => 'required',
			'firstName' => 'required',
			'lastName' => 'required',
			'gender' => 'required',
			'religion' => 'required',
			'bloodgroup' => 'required',
			'nationality' => 'required',
			'dob' => 'required',
			'photo' => 'mimes:jpeg,jpg,png',
			'mobileNo' => 'required',
			'fatherName' => 'required',
			'fatherMobileNo' => 'required',
			'motherName' => 'required',
			'motherMobileNo' => 'required',
			'presentAddress' => 'required',
			'parmanentAddress' => 'required'
		];
		$validator = Validator::make($data, $rules);
		$errors=$validator->messages()->toArray();
		if ($validator->fails())
		{
			return Response()->json([
				'error' => true,
				'message' => $errors
			], 400);
		}
		else {
			try {
				$student = Student::findOrFail($id);
				if($request->exists('photo'))
				{

					$directory = public_path() . "/assets/images/students/";
					unlink($directory.$student->photo);
					$fextention = $data['photo']->getClientOriginalExtension();
					$fileName=str_replace(' ','_',$student->idNo).'.'.$fextention;
					$data['photo']->move($directory,$fileName);
					$data['photo']=$fileName;
				}
				else{
					$data['photo']=$student->photo;
				}
				$data['department_id']=$student->department_id;
				$data['session']=$student->session;
				$data['idNo']=$student->idNo;
				$student->fill($data)->save();
				return Response()->json([
					'success' => true,
					'message' => "Student Information Updated Succesfully."
				], 200);
			}
			catch (Exception $e)
			{
				$notification= array('title' => 'Data Update', 'body' => "There is no record.");
				return Redirect::route('student.index')->with("error",$notification);
			}
		}
	}


	/**
	* Remove the specified resource from storage.
	*
	* @param  int  $id
	* @return Response
	*/
	public function destroy($id)
	{
		$student = Student::findOrFail($id);
		$student->delete();
		$notification= array('title' => 'Data Delete', 'body' => 'Student Deleted Succesfully.');
		return Redirect::route('student.index')->with("success",$notification);
	}

	/**
	*These below code is responsible for
	*student registration
	*
	*
	*/
	public function regCreate()
	{
		$students=[];
		$semesters= $this->semesters;
		$departments = Department::select('id','name')->orderby('name','asc')->lists('name', 'id');
		return view('student.registration.create',compact('departments','students','semesters'));
	}
	public function regStore(Request $request){
		$data=$request->all();
		$rules=[
			'department_id' => 'required',
			'students_id' => 'required',
			'levelTerm' => 'required'
		];
		$validator = Validator::make($data, $rules);
		$errors=$validator->messages()->toArray();
		if ($validator->fails()){
			return Response()->json([
				'error' => true,
				'message' => $errors
			], 400);
		}
		$isExists = Registration::where('department_id',$data['department_id'])
		->where('students_id',$data['students_id'])
		->where('levelTerm',$data['levelTerm'])->first();

		if($isExists){
			return Response()->json([
				'error' => true,
				'message' => ['Data Exists'=>"This student already registered!"]
			], 400);
		}
		Registration::create($data);
		$notification= array('title' => 'Data Store', 'body' => 'Registration Successfull.');
		return Response()->json([
			'success' => true,
			'message' => $notification
		], 200);


	}

	public function regIndex(){
		$departments = Department::select('id','name')->orderby('name','asc')->lists('name', 'id');
		$selectDep="";
		$students =array();
		$semesters= $this->semesters;
		$selectSem="";
		$session="";
		return view('student.registration.index',compact('session','students','departments','selectDep','semesters','selectSem'));
	}
	public function regList(Request $request){

		 $students=Registration::with(array('student' =>  function($query){
						$query->select('id','idNo','firstName','middleName','lastName','photo');
			}))
			->where('department_id',$request->input('department_id'))
			->where('session',$request->input('session'))
			->where('levelTerm',$request->input('levelTerm'))
			->get();

		$departments = Department::select('id','name')->orderby('name','asc')->lists('name', 'id');
		$selectDep=$request->input('department_id');
		$semesters= $this->semesters;
		$selectSem=$request->input('levelTerm');
		$session=$request->input('session');
		return view('student.registration.index',compact('session','students','departments','selectDep','semesters','selectSem'));

	}
	public function regDestroy($id)
	{
		$student=Registration::findOrFail($id);
		$student->delete();
		$notification= array('title' => 'Data Delete', 'body' => 'Cancel student registration.');
		return Response()->json([
			'success' => true,
			'message' => $notification
		], 200);

	}


}
