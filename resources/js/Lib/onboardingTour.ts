// Lazy import so driver.js (~5 KB) only loads on pages that actually use it.
export async function startMyAppsTour(): Promise<void> {
    const { driver } = await import('driver.js');
    await import('driver.js/dist/driver.css');

    const tour = driver({
        animate: false, // respects "no animations" project preference
        showProgress: true,
        nextBtnText: 'Next',
        prevBtnText: 'Back',
        doneBtnText: 'Got it',
        steps: [
            {
                element: '[data-tour="search"]',
                popover: {
                    title: 'Find your app',
                    description: 'Type your app name, or paste its Shopify URL. We can search the full Shopify catalogue.',
                },
            },
            {
                element: '[data-tour="results"]',
                popover: {
                    title: 'Pick it',
                    description: 'Click a result to add it as yours. If we don\'t have it yet, we\'ll fetch it on the spot.',
                },
            },
            {
                element: '[data-tour="my-list"]',
                popover: {
                    title: 'Your apps live here',
                    description: 'These power your dashboard. Add or remove apps any time from this page.',
                },
            },
        ],
    });

    tour.drive();
}
