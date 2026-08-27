@extends('layouts.admin', ['title' => '编辑学生'])

@section('content')
<h1 class="text-xl font-bold text-slate-900 mb-4">编辑学生：{{ $student->name }}</h1>
@include('admin.students._form')
@endsection
