<?php include "header.php"; ?>

    <div class="container mt-4">

        <div class="row mb-3">

            <div class="col-md-4">
                <label for="searchKeyword" class="form-label"><b>Search keyword</b></label>
                <input type="text" id="searchKeyword" class="form-control" placeholder="Search keyword">
            </div>

            <div class="col-md-4">
                <label for="filterType" class="form-label"><b>Select Type(s)</b></label>
                <select id="filterType" class="selectpicker" data-width="100%">
                    <option value="">All Types</option>
                    <option value="video">Video</option>
                    <option value="article">Article</option>
                </select>
            </div>

            <div class="col-md-4 d-flex align-items-end">
                <button id="searchBtn" class="btn btn-primary w-100">Search</button>
            </div>

            <hr class="mt-5">

            <div class="col-md-12 mt-3 position-relative">

                <div id="clientSearchContainer" class="position-absolute" style="top:0; right:0; z-index:1; width:250px;">
                    <input type="text" id="clientSearch" class="form-control form-control-sm" placeholder="Filter visible rows">
                </div>

                <table id="contentTable" class="table table-hover table-bordered" style="width:100%">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Type</th>
                            <th>Score</th>
                            <th>Views</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>


<?php include "footer.php"; ?>

<script src="js/dashboard.js"></script>