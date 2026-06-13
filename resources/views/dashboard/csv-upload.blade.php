@extends('layouts.dashboard')

@section('title', 'CSV Upload')

@section('content')
    <div class="content-card">
        <h2><i class="fas fa-upload"></i> Import Products from CSV</h2>
        <p>Upload a Shopify product CSV file to import products into the system.</p>

        <!-- Sample CSV Download -->
        <div class="alert alert-info mb-4" role="alert">
            <i class="fas fa-info-circle"></i>
            <strong>Sample CSV File:</strong> Download the
            <a href="{{ asset('shopifyproduct/shopify-products-csv.csv') }}" class="alert-link">sample Shopify products CSV</a>
            to see the required format.
        </div>

        <!-- Upload Form -->
        <div class="card mb-4" style="border: 2px dashed #667eea; background: #f8f9ff;">
            <div class="card-body">
                <form id="csvUploadForm" enctype="multipart/form-data" class="text-center">
                    @csrf
                    <div class="mb-3">
                        <label for="csvFileInput" class="form-label" style="font-size: 16px; font-weight: 600;">
                            <i class="fas fa-file-csv" style="color: #667eea; font-size: 32px;"></i><br>
                            Select CSV File
                        </label>
                        <input type="file" id="csvFileInput" name="csv_file" accept=".csv" class="form-control d-none" required>
                        <div id="fileNameDisplay" style="margin-top: 10px; color: #666; font-weight: 500;">
                            No file selected
                        </div>
                    </div>

                    <div class="d-flex justify-content-center align-items-center flex-wrap gap-2 mb-3">
                        <button type="button" id="selectFileBtn" class="btn btn-primary btn-lg">
                            <i class="fas fa-folder-open"></i> Choose File
                        </button>

                        <button type="submit" id="submitBtn" class="btn btn-success btn-lg" style="display: none;">
                            <i class="fas fa-cloud-upload-alt"></i> Upload CSV
                        </button>
                    </div>

                    <div id="uploadProgress" style="display: none; margin-top: 20px;">
                        <div class="progress" style="height: 25px; margin-bottom: 15px;">
                            <div id="progressBar" class="progress-bar progress-bar-striped progress-bar-animated"
                                 role="progressbar" style="width: 0%" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">
                                <span id="progressText">0%</span>
                            </div>
                        </div>
                        <p id="progressMessage" style="color: #666;">Uploading...</p>
                    </div>
                </form>
            </div>
        </div>

        <!-- Status Messages -->
        <div id="statusContainer"></div>

        <!-- Import Details -->
        <div id="importDetails" style="display: none;">
            <div class="card">
                <div class="card-header bg-light">
                    <h5 class="mb-0"><i class="fas fa-info-circle"></i> Import Status</h5>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-3">
                            <div class="stat-card">
                                <i class="fas fa-check-circle" style="color: #28a745;"></i>
                                <div class="stat-value" id="importedCount">0</div>
                                <div class="stat-label">Imported</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-card">
                                <i class="fas fa-times-circle" style="color: #dc3545;"></i>
                                <div class="stat-value" id="failedCount">0</div>
                                <div class="stat-label">Failed</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-card">
                                <i class="fas fa-file-alt" style="color: #667eea;"></i>
                                <div class="stat-value" id="totalCount">0</div>
                                <div class="stat-label">Total Rows</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-card">
                                <i class="fas fa-hourglass-half" id="statusIcon" style="color: #ffc107;"></i>
                                <div class="stat-value" style="font-size: 14px;" id="statusBadge">Processing...</div>
                                <div class="stat-label">Status</div>
                            </div>
                        </div>
                    </div>

                    <div id="errorContainer" style="display: none;">
                        <div class="alert alert-danger" role="alert">
                            <h5><i class="fas fa-exclamation-triangle"></i> Errors Found</h5>
                            <pre id="errorMessage" style="margin-bottom: 0; max-height: 300px; overflow-y: auto;"></pre>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Import History -->
    <div class="content-card">
        <h2><i class="fas fa-history"></i> Import History</h2>
        <div id="importHistoryContainer">
            <p style="color: #999; text-align: center;">Loading history...</p>
        </div>
    </div>

    <!-- Import Errors Modal -->
    <div class="modal fade" id="importErrorsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-exclamation-triangle text-danger"></i> Import Errors</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <pre id="importErrorsBody" style="white-space: pre-wrap; word-break: break-word; max-height: 400px; overflow-y: auto; background: #f8f9fa; padding: 15px; border-radius: 6px; margin: 0;"></pre>
                </div>
            </div>
        </div>
    </div>

    <style>
        .stat-card {
            background: white;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
        }

        .stat-card i {
            font-size: 24px;
            margin-bottom: 10px;
        }

        .stat-card .stat-value {
            font-size: 20px;
            font-weight: 700;
            color: #333;
        }

        .stat-card .stat-label {
            font-size: 12px;
            color: #999;
            text-transform: uppercase;
            margin-top: 5px;
        }
    </style>

    <script>
        const selectFileBtn = document.getElementById('selectFileBtn');
        const csvFileInput = document.getElementById('csvFileInput');
        const fileNameDisplay = document.getElementById('fileNameDisplay');
        const submitBtn = document.getElementById('submitBtn');
        const uploadProgress = document.getElementById('uploadProgress');
        const csvUploadForm = document.getElementById('csvUploadForm');
        const statusContainer = document.getElementById('statusContainer');
        const importDetails = document.getElementById('importDetails');

        let currentImportId = null;
        let statusCheckInterval = null;
        let importErrorsMap = {};
        let importErrorsModal = null;

        function showImportErrors(importId) {
            const body = document.getElementById('importErrorsBody');
            body.textContent = importErrorsMap[importId] || 'No error details available.';
            if (!importErrorsModal) {
                importErrorsModal = new bootstrap.Modal(document.getElementById('importErrorsModal'));
            }
            importErrorsModal.show();
        }

        // File selection
        selectFileBtn.addEventListener('click', () => {
            csvFileInput.click();
        });

        csvFileInput.addEventListener('change', (e) => {
            const file = e.target.files[0];
            if (file) {
                fileNameDisplay.textContent = file.name;
                submitBtn.style.display = 'inline-block';
                selectFileBtn.innerHTML = '<i class="fas fa-folder-open"></i> Change File';
            }
        });

        // Form submission
        csvUploadForm.addEventListener('submit', async (e) => {
            e.preventDefault();

            if (!csvFileInput.files[0]) {
                showStatus('Please select a CSV file', 'error');
                return;
            }

            const formData = new FormData();
            formData.append('csv_file', csvFileInput.files[0]);
            formData.append('_token', document.querySelector('[name="_token"]').value);

            // Show progress
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Uploading...';
            uploadProgress.style.display = 'block';

            try {
                const response = await fetch('{{ route("csv-import.store") }}', {
                    method: 'POST',
                    body: formData,
                });

                const data = await response.json();

                if (data.success) {
                    currentImportId = data.import_id;
                    showStatus(data.message, 'success');
                    importDetails.style.display = 'block';

                    // Start polling for status
                    startStatusCheck();

                    // Reset form
                    csvUploadForm.reset();
                    fileNameDisplay.textContent = 'No file selected';
                    submitBtn.style.display = 'none';
                    selectFileBtn.innerHTML = '<i class="fas fa-folder-open"></i> Choose File';
                } else {
                    showStatus(data.message, 'error');
                }
            } catch (error) {
                showStatus('Error uploading file: ' + error.message, 'error');
            } finally {
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-cloud-upload-alt"></i> Upload CSV';
                uploadProgress.style.display = 'none';
            }
        });

        // Check import status
        function startStatusCheck() {
            statusCheckInterval = setInterval(checkImportStatus, 2000); // Check every 2 seconds
        }

        async function checkImportStatus() {
            if (!currentImportId) return;

            try {
                const response = await fetch(`{{ route('csv-import.check-status', ':id') }}`.replace(':id', currentImportId));
                const data = await response.json();

                if (data.success) {
                    updateImportDetails(data);

                    if (data.status === 'completed' || data.status === 'failed') {
                        clearInterval(statusCheckInterval);
                        if (data.status === 'completed') {
                            showStatus('✓ Import completed successfully!', 'success');
                        } else {
                            showStatus('✗ Import failed. Please check the errors below.', 'error');
                        }
                        loadImportHistory();
                    }
                }
            } catch (error) {
                console.error('Status check error:', error);
            }
        }

        function updateImportDetails(data) {
            document.getElementById('importedCount').textContent = data.imported_rows;
            document.getElementById('failedCount').textContent = data.failed_rows;
            document.getElementById('totalCount').textContent = data.total_rows;

            let statusText = 'Processing...';
            let statusIcon = 'fa-hourglass-half';
            let statusColor = '#ffc107';

            if (data.status === 'completed') {
                statusText = 'Completed';
                statusIcon = 'fa-check-circle';
                statusColor = '#28a745';
            } else if (data.status === 'failed') {
                statusText = 'Failed';
                statusIcon = 'fa-times-circle';
                statusColor = '#dc3545';
            } else if (data.status === 'processing') {
                statusText = 'Processing...';
                statusIcon = 'fa-spinner fa-spin';
                statusColor = '#667eea';
            }

            const statusBadge = document.getElementById('statusBadge');
            statusBadge.innerHTML = `<span style="color: ${statusColor};">${statusText}</span>`;

            const statusIconEl = document.getElementById('statusIcon');
            statusIconEl.className = `fas ${statusIcon}`;
            statusIconEl.style.color = statusColor;

            if (data.error_message) {
                document.getElementById('errorContainer').style.display = 'block';
                document.getElementById('errorMessage').textContent = data.error_message;
            }
        }

        function showStatus(message, type) {
            const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
            const icon = type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle';

            const alertHtml = `
                <div class="alert ${alertClass} alert-dismissible fade show" role="alert">
                    <i class="fas ${icon}"></i> ${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            `;

            statusContainer.innerHTML = alertHtml;
        }

        // Load import history
        async function loadImportHistory() {
            try {
                const response = await fetch('{{ route("csv-import.history") }}');
                const data = await response.json();

                if (data.success) {
                    renderImportHistory(data.imports);
                }
            } catch (error) {
                console.error('Error loading history:', error);
            }
        }

        function renderImportHistory(imports) {
            if (imports.length === 0) {
                document.getElementById('importHistoryContainer').innerHTML =
                    '<p style="color: #999; text-align: center;">No import history yet</p>';
                return;
            }

            let html = `
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>File Name</th>
                                <th>Status</th>
                                <th>Total Rows</th>
                                <th>Imported</th>
                                <th>Failed</th>
                                <th>Date</th>
                                <th>Errors</th>
                            </tr>
                        </thead>
                        <tbody>
            `;

            importErrorsMap = {};

            imports.forEach(imp => {
                const statusBadge = getStatusBadge(imp.status);
                const date = new Date(imp.created_at).toLocaleString();

                let errorCell = '<span class="text-muted">—</span>';
                if (imp.error_message) {
                    importErrorsMap[imp.id] = imp.error_message;
                    errorCell = `<button class="btn btn-sm btn-outline-danger" onclick="showImportErrors(${imp.id})">
                        <i class="fas fa-exclamation-circle"></i> View Errors
                    </button>`;
                }

                html += `
                    <tr>
                        <td>${imp.file_name}</td>
                        <td>${statusBadge}</td>
                        <td>${imp.total_rows}</td>
                        <td><span class="badge bg-success">${imp.imported_rows}</span></td>
                        <td><span class="badge bg-danger">${imp.failed_rows}</span></td>
                        <td>${date}</td>
                        <td>${errorCell}</td>
                    </tr>
                `;
            });

            html += `
                        </tbody>
                    </table>
                </div>
            `;

            document.getElementById('importHistoryContainer').innerHTML = html;
        }

        function getStatusBadge(status) {
            const badges = {
                'pending': '<span class="badge bg-secondary">Pending</span>',
                'processing': '<span class="badge bg-info">Processing...</span>',
                'completed': '<span class="badge bg-success">Completed</span>',
                'failed': '<span class="badge bg-danger">Failed</span>',
            };
            return badges[status] || '<span class="badge bg-secondary">Unknown</span>';
        }

        // Load history on page load
        loadImportHistory();
    </script>
@endsection
