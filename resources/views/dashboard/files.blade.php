@extends('layouts.dashboard')

@section('title', 'My Files')

@section('content')
    <div class="content-card">
        <h2><i class="fas fa-file-upload"></i> My Files</h2>
        <p>Upload and manage your files here.</p>

        <div class="mt-4">
            <form action="#" method="POST" enctype="multipart/form-data">
                @csrf

                <div class="mb-3">
                    <label for="fileInput" class="form-label">Upload File</label>
                    <input type="file" class="form-control" id="fileInput" name="file">
                    <small class="text-muted">Max file size: 50MB</small>
                </div>

                <button type="submit" class="btn btn-success">
                    <i class="fas fa-cloud-upload-alt"></i> Upload
                </button>
            </form>
        </div>

        <hr>

        <h5 class="mt-4">Your Files</h5>
        <div class="alert alert-info" role="alert">
            <i class="fas fa-info-circle"></i> No files uploaded yet. Upload your first file above!
        </div>
    </div>
@endsection
