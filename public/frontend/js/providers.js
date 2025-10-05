$(document).ready(function () {
    // Provider listesi DataTable
    const providerTable = $("#providersTable").DataTable({
        ajax: {
            url: "/api/providers",
            type: "GET",
            dataSrc: "",
            error: function (xhr) {
                if (xhr.status === 429) {
                    const retry = xhr.responseJSON?.retry_after ?? 60;
                    toastr.warning(`Too many requests. Please wait ${retry} seconds.`, "Rate limit");
                }
                else {
                    toastr.error("An error occurred while loading the table.");
                }
            }
        },
        columns: [
            { data: "id" },
            { data: "name" },
            { data: "url" },
            { data: "format" },
            {
                data: "active",
                render: function (data) {
                    return data ? '<span class="badge bg-success">Yes</span>' : '<span class="badge bg-danger">No</span>';
                }
            },
            {
                data: "createdAt",
                render: function (data) {
                    return data ? new Date(data).toLocaleString() : "";
                }
            },
            {
                data: "id",
                render: function (id, type, row) {
                    return `<button class="btn btn-sm btn-danger delete-provider"
                        data-id="${id}"
                        data-active="${row.active}">
                    Delete
                </button>`;
                }
            }
        ]
    });

    // Provider ekleme
    $("#providerForm").on("submit", function (e) {
        e.preventDefault();
        const formData = $(this).serialize();

        $.ajax({
            url: "/api/providers",
            type: "POST",
            data: formData,
            success: function () {
                toastr.success("Provider added successfully!");
                providerTable.ajax.reload();
                $("#providerForm")[0].reset();
            },
            error: function (xhr) {
                if (xhr.status === 429) {
                    const retry = xhr.responseJSON?.retry_after ?? 10;
                    toastr.warning(`Too many requests. Please wait ${retry} seconds.`, "Rate limit");
                }
                else {
                    toastr.error(xhr.responseJSON?.message || "Error adding provider");
                }
            }
        });
    });

    // Provider silme
    $("#providersTable").on("click", ".delete-provider", function () {
        const id = $(this).data("id");
        const active = $(this).data("active"); // 1 veya 0

        // Swal config objesini baştan oluştur
        let swalConfig = {
            title: "Delete Provider?",
            icon: "warning",
            showCancelButton: true,
            cancelButtonText: "Cancel",
        };

        if (active == 1) {
            // Aktif provider için: hem soft delete hem de permanent delete
            swalConfig.text = "Do you want to make it inactive or delete completely?";
            swalConfig.showDenyButton = true;
            swalConfig.confirmButtonText = "Make Inactive";
            swalConfig.denyButtonText = "Delete Completely";
        }
        else {
            // Pasif provider için: sadece permanent delete
            swalConfig.text = "This provider is already inactive. Delete completely?";
            swalConfig.showDenyButton = false;
            swalConfig.confirmButtonText = "Delete Completely";
        }

        Swal.fire(swalConfig).then((result) => {
            if (active == 1 && result.isConfirmed) {
                // Aktif ve soft delete seçildi → active=0 ve content sil
                $.ajax({
                    url: `/api/providers/${id}/soft-delete`,
                    type: "POST",
                    success: function () {
                        toastr.success("Provider set to inactive and related contents deleted");
                        providerTable.ajax.reload();
                    },
                    error: function (xhr) {
                        if (xhr.status === 429) {
                            const retry = xhr.responseJSON?.retry_after ?? 10;
                            toastr.warning(`Too many requests. Please wait ${retry} seconds.`, "Rate limit");
                        }
                        else {
                            toastr.error("Error updating provider");
                        }
                    }
                });
            }
            else if ((active == 1 && result.isDenied) || (active == 0 && result.isConfirmed)) {
                // Permanent delete
                Swal.fire({
                    title: "Are you sure?",
                    text: "This will permanently delete the provider and all related contents!",
                    icon: "error",
                    showCancelButton: true,
                    confirmButtonText: "Yes, delete permanently",
                    cancelButtonText: "Cancel",
                }).then((confirmResult) => {
                    if (confirmResult.isConfirmed) {
                        $.ajax({
                            url: `/api/providers/${id}`,
                            type: "DELETE",
                            success: function () {
                                toastr.success("Provider and related contents permanently deleted");
                                providerTable.ajax.reload();
                            },
                            error: function (xhr) {
                                if (xhr.status === 429) {
                                    const retry = xhr.responseJSON?.retry_after ?? 10;
                                    toastr.warning(`Too many requests. Please wait ${retry} seconds.`, "Rate limit");
                                }
                                else {
                                    toastr.error("Error deleting provider");
                                }
                            }
                        });
                    }
                });
            }
        });
    });

    // Import başlatma
    $("#importBtn").on("click", function () {
        $.ajax({
            url: "/api/providers/import",
            type: "POST",
            success: function (res) {
                toastr.info(`Import completed. Imported: ${res.imported} provider(s).`);
                providerTable.ajax.reload();
            },
            error: function (xhr) {
                if (xhr.status === 429) {
                    const retry = xhr.responseJSON?.retry_after ?? 10;
                    toastr.warning(`Too many requests. Please wait ${retry} seconds.`, "Rate limit");
                }
                else {
                    toastr.error("Error starting import");
                }
            }
        });
    });
});
