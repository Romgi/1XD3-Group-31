/**
 * Sends an AJAX request to the user.
 */
window.addEventListener("load", function () {
    document.getElementById("concert").addEventListener("submit", async (e) => {

        const formData = new FormData(e.target);

        let response = await fetch('concert_create.php', {
            method: "POST",
            body: formData
        })

        const text = await response.text();  // read the body
        console.log(text);


    })
    document.getElementById("part").addEventListener("submit", async (e) => {
        const formData = new FormData(e.target);

        let response = await fetch('part_create.php', {
            method: "POST",
            body: formData
        })

        const text = await response.text();  // read the body
        console.log(text);


    })
    document.getElementById("recording_form").addEventListener("submit", async (e) => {

        const formData = new FormData(e.target);

        let response = await fetch('reference_create.php', {
            method: "POST",
            body: formData
        })

        const text = await response.text();  // read the body
        console.log(text);

    })
    document.getElementById("link_member").addEventListener("submit", async (e) => {
        const formData = new FormData(e.target);

        let response = await fetch('memberlink.php', {
            method: "POST",
            body: formData
        })

        const text = await response.text();  // read the body
        console.log(text);


    })
    document.getElementById("create_member").addEventListener("submit", async (e) => {
        const formData = new FormData(e.target);

        let response = await fetch('member_create.php', {
            method: "POST",
            body: formData
        })

        const text = await response.text();  // read the body
        console.log(text);


    })
});