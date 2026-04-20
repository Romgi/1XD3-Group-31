/*
    Name(s): Jonathan, Marco, Charles, Hanzhi
    Date Created: April 2026
    File Description: Handles asynchronous admin dashboard form submissions and status message updates.
*/

/**
 * Updates the admin status banner after a form submission.
 *
 * @param {string} message The message to display to the administrator.
 * @param {boolean} isError Indicates whether the message represents an error state.
 * @returns {void} This function does not return a value.
 */
function setAdminStatus(message, isError = false) {
    const status = document.getElementById("admin-status");
    if (!status) {
        return;
    }

    status.textContent = message;
    status.classList.toggle("admin-status-error", isError);
    status.classList.toggle("admin-status-success", !isError && message !== "");
}

/**
 * Submits an admin form asynchronously and returns the response message.
 *
 * @param {HTMLFormElement} form The admin form element being submitted.
 * @returns {Promise<string>} The resolved server response message.
 */
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
                const message = error instanceof Error
                    ? error.message
                    : "The request could not be completed.";
                setAdminStatus(message, true);
            }
        });
    });
});
