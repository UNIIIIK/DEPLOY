$(document).ready(function () {
    loadUsers();
    // LOGIN FUNCTION
    $("#loginForm").submit(function (event) {
        event.preventDefault(); // Prevent default form submission

        let username = $("#username").val();
        let password = $("#password").val();

        $.ajax({
            url: "login_process.php",
            type: "POST",
            data: { username: username, password: password },
            dataType: "json",
            success: function (response) {
                if (response.status === "success") {
                    window.location.href = "index.php"; // Redirect upon successful login
                } else {
                    $("#loginError").html(response.message).show(); // Show error message
                }
            },
            error: function () {
                $("#loginError").html("An error occurred. Please try again.").show();
            }
        });
    });

    $("#btnSaveUser").click(function () {
        let formData = new FormData($("#newUserForm")[0]);
        let userId = $("#user_id").val();
        let url = userId ? "update_user.php" : "add_user.php";

        $.ajax({
            url: url,
            type: "POST",
            data: formData,
            processData: false,
            contentType: false,
            dataType: "JSON",
            success: function (response) {
                if (response.status === "success") {
                    alert(userId ? "User updated successfully!" : "User added successfully!");
                    loadUsers();

                    $("#exampleModal").modal("hide");
                    $("#newUserForm")[0].reset();
                    $("#user_id").val(""); 
                    $("#profileImagePreview").attr("src", "default.jpg"); 

                    setTimeout(() => {
                        $(".modal-backdrop").remove();
                        $("body").removeClass("modal-open");
                    }, 300);
                } else {
                    alert("Error: " + response.message);
                }
            },
            error: function (jqXHR, textStatus, errorThrown) {
                alert("Error: " + textStatus + " - " + errorThrown);
            }
        });
    });

    function loadUsers() {
        $.ajax({
            url: "fetch_user.php",
            type: "GET",
            success: function (data) {
                $("#userTable").html(data);

                $("#searchUser").off("keyup").on("keyup", function () {
                    let searchText = $(this).val().toLowerCase();
                    $("#userTable tr").each(function () {
                        $(this).toggle($(this).text().toLowerCase().includes(searchText));
                    });
                });
            },
            error: function () {
                alert("Failed to load users.");
            }
        });
    }

    $(document).on("click", ".editBtn", function () {
        let userId = $(this).data("student-id");

        $.ajax({
            url: "get_user.php",
            type: "POST",
            data: { id: userId },
            dataType: "JSON",
            success: function (data) {
                if (data.error) {
                    alert(data.error);
                } else {
                    $("#user_id").val(data.student_id);
                    $("#firstName").val(data.first_name);
                    $("#lastName").val(data.last_name);
                    $("#Email").val(data.email);
                    $("#Gender").val(data.gender);
                    $("#Address").val(data.user_address);
                    $("#Birthdate").val(data.birthdate);
                    $("#Course").val(data.course);

                    if (data.profile_image) {
                        $("#profileImagePreview").attr("src", data.profile_image);
                    } else {
                        $("#profileImagePreview").attr("src", "default.jpg");
                    }

                    $("#exampleModal").modal("show");
                }
            }
        });
    });

    $("#profileImage").change(function () {
        let reader = new FileReader();
        reader.onload = function (e) {
            $("#profileImagePreview").attr("src", e.target.result);
        };
        reader.readAsDataURL(this.files[0]);
    });

    $(document).on("click", ".deleteBtn", function () {
        let userId = $(this).data("student-id");

        if (confirm("Are you sure you want to delete this user?")) {
            $.ajax({
                url: "delete_user.php",
                type: "POST",
                data: { id: userId },
                dataType: "JSON",
                success: function (response) {
                    if (response.status === "success") {
                        alert(response.message);
                        loadUsers();
                    } else {
                        alert("Error: " + response.message);
                    }
                },
                error: function () {
                    alert("Failed to delete user.");
                }
            });
        }
    });

   
   
});
