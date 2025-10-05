$(document).ready(function() {
    $(".selectpicker").selectpicker();

    const table = $("#contentTable").DataTable({
        processing: true,
        serverSide: true,
        searching: false,
        lengthMenu: [[5, 10, 25, 50, -1], [5, 10, 25, 50, "All"]],
        ajax: {
            url: "/api/contents",
            type: "POST",
            data: function(d) {
                d.type = $("#filterType").val();
                d.keyword = $("#searchKeyword").val();

                //d.disableRateLimit = false;
            },
            error: function(xhr) {
                if (xhr.status === 429) {
                    const retry = xhr.responseJSON?.retry_after ?? 10;
                    toastr.warning(`Too many requests. Please wait ${retry} seconds.`, "Rate limit");
                } else {
                    toastr.error("Unexpected server error", "Error");
                }
            }
        },
        columns: [
            { data: "title" },
            { data: "type" },
            { data: "score" },
            { data: "views" }
        ],
        order: [[2, "desc"]]
    });


    $("#searchBtn").on("click", function() {
        table.ajax.reload();
    });


    $("#clientSearch").on("keyup", function() {
        const val = this.value.toLowerCase();

        table.rows({search: "applied"}).every(function() {
            const rowData = this.data();
            let match = false;

            for (const key in rowData) {
                if (rowData[key] && rowData[key].toString().toLowerCase().includes(val)) {
                    match = true;
                    break;
                }
            }

            if(match) {
                $(this.node()).show();

                $(this.node()).find("td").each(function() {
                    const originalText = $(this).text();
                    const regex = new RegExp("(" + val + ")", "gi");
                    $(this).html(originalText.replace(regex, "<mark>$1</mark>"));
                });

            }
            else {
                $(this.node()).hide();
            }
        });


        if(val === "") {
            table.rows().every(function() {
                $(this.node()).find("td").each(function() {
                    $(this).html($(this).text());
                });
            });
        }
    });


});
