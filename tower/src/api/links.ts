import {ApiUrl} from "@/constants/api";

export function addLink(outwardTaskId: string, inwardTaskId: string, type: string)
{
    return fetch(ApiUrl.LINKS, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            outwardJiraId: outwardTaskId,
            inwardJiraId: inwardTaskId,
            type: type,
        }),
    });
}
