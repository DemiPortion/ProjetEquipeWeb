const loader = document.getElementById('loader');
const form = document.getElementById('generate-form');

form.addEventListener('submit', async function (e) {
    e.preventDefault();

    const description = document.getElementById('description').value;
    const resultDiv = document.getElementById('result');

    if (!description) {
        alert('Please enter a description!');
        return;
    }

    loader.style.display = 'block';
    resultDiv.innerHTML = '';

    try {
        const response = await fetch('generate.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ description })
        });

        if (!response.ok) {
            throw new Error(`HTTP error! Status: ${response.status}`);
        }

        const text = await response.text();
        console.log('Raw Response:', text);

        let data;
        try {
            data = JSON.parse(text);
        } catch (jsonError) {
            console.error('JSON Parsing Error:', jsonError);
            resultDiv.innerHTML = 'Invalid JSON response from server.';
            return;
        }

        console.log('Parsed JSON:', data);

        let link = (data.links && data.links[0]) ? data.links[0] : null;

        if (link) {
            var url = window.location.href;
            if (url.endsWith('index.html')) {
                url = url.replace('/index.html', '');
            }

            resultDiv.innerHTML = `<a href="${url + link}" target="_blank">View Generated Page</a>`;
        } else {
            resultDiv.innerHTML = 'Error generating the page. No links found.';
        }
    } catch (error) {
        console.error('Error:', error);
        resultDiv.innerHTML = 'An error occurred. Please try again.';
    } finally {
        loader.style.display = 'none';
    }
});
