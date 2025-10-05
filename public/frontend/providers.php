<?php include "header.php"; ?>

    <div class="container mt-4">
        <div class="row">
            <form id="providerForm" enctype="multipart/form-data" class="w-100">
                <h3 class="mt-5">Add Provider</h3>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="providerName" class="form-label">Provider Name</label>
                        <input type="text" class="form-control" id="providerName" name="name" required>
                    </div>

                    <div class="col-md-6">
                        <label for="providerFormat" class="form-label">Format</label>
                        <select id="providerFormat" name="format" class="form-select" required>
                            <option value="json">JSON</option>
                            <option value="xml">XML</option>
                        </select>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="providerUrl" class="form-label">Provider URL</label>
                        <input type="url" class="form-control" id="providerUrl" name="url">
                    </div>

                    <div class="col-md-6 d-flex align-items-end">
                        <button type="submit" class="btn btn-success w-100">Add Provider</button>
                    </div>
                </div>
            </form>

            <div class="mt-3 col-md-12">
                <button type="button" id="importBtn" class="btn btn-warning w-100">Import providers</button>
            </div>

            <div class="mt-3 col-md-12">
                <h3 class="mt-5">Providers List</h3>
                <table class="table table-bordered mt-3" id="providersTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>URL</th>
                            <th>Format</th>
                            <th>Active</th>
                            <th>Created At</th>
                            <th>Delete</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>

<?php include "footer.php"; ?>

<script src="js/providers.js"></script>
