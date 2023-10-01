import {ApiUrl} from "@/constants/api";

export function addLink(outwardTaskId: string, inwardTaskId: string, type: string)
{
    return fetch(ApiUrl.LINKS, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            outwardTaskId: outwardTaskId,
            inwardTaskId: inwardTaskId,
            type: type,
        }),
    });
}

export function removeLink(from: string, to: string, type: string)
{
    const url = ApiUrl.LINK
        .replace('{from}', `${from}`)
        .replace('{to}', `${to}`)
        .replace('{type}', `${type}`);
    return fetch(url, {
        method: 'DELETE',
        headers: {
            'Content-Type': 'application/json'
        },
    });
}
