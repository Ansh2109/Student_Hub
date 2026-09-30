    // Fetch JSON data

export async function fetchData(file) {
    const response = await fetch(file);

    if (!response.ok) {
        throw new Error("Unable to fetch " + file);
    }

    const data = await response.json();

    return data;
}