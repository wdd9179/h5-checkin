@extends('layouts.admin', ['title' => '添加学生'])

@section('content')
<h1 class="text-xl font-bold text-slate-900 mb-4">添加学生</h1>
@include('admin.students._form')
@endsection
