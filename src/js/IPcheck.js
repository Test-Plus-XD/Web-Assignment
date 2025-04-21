// Check IP and decide whether to show reCAPTCHA
fetch("IPCheck.php")
    .then(res => res.json())
    .then(data => {
        if (!data.success) throw new Error("API failed");
        console.log("IPData check result:", data);

        const suspicious = data.suspicious;
        const delay = suspicious ? 500 : 10;

        if (!window.location.search.includes("checked=1")) {
            setTimeout(() => {
                const url = new URL(window.location.href);
                url.searchParams.set("recaptchaFlag", suspicious ? "1" : "0");
                url.searchParams.set("checked", "1");
                window.location.replace(url.toString());
            }, delay);
        }
    })
    .catch(err => {
        console.error("Backend IP check failed:", err);
        // fallback to reCAPTCHA if backend fails
        if (!window.location.search.includes("checked=1")) {
            const url = new URL(window.location.href);
            url.searchParams.set("recaptchaFlag", "1");
            url.searchParams.set("checked", "1");
            window.location.href = url.toString();
        }
    });