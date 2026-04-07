function setAdminStatus(message, isError = false) {
    const status = document.getElementById("admin-status");
    if (!status) {
        return;
    }

    status.textContent = message;
    status.classList.toggle("admin-status-error", isError);
    status.classList.toggle("admin-status-success", !isError && message !== "");
}

async function submitAdminForm(form) {
    const response = await fetch(form.action, {
        method: "POST",
        body: new FormData(form),
        headers: {
            "Accept": "application/json"
        }
    });

    const contentType = response.headers.get("content-type") || "";
    const payload = contentType.includes("application/json")
        ? await response.json()
        : { message: await response.text() };

    if (!response.ok || payload.ok === false) {
        throw new Error(payload.message || "The request could not be completed.");
    }

    return payload.message || "Saved.";
}

window.addEventListener("DOMContentLoaded", () => {
    document.querySelectorAll(".admin-form").forEach((form) => {
        form.addEventListener("submit", async (event) => {
            event.preventDefault();
            setAdminStatus("Saving...");

            try {
                const message = await submitAdminForm(form);
                setAdminStatus(message);
                form.reset();
            } catch (error) {
                setAdminStatus(error.message, true);
            }
        });
    });
});
