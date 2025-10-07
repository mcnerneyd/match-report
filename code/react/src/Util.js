export function takeWhile(arr, predicate) {
    const kx = arr.findIndex(predicate)
    return [...(kx < 0 ? arr : arr.slice(0,kx))]
}