import {ApiUrl} from "@/constants/api";
import {Issue} from "@/types/Issue";
import {cleanObject} from "@/utils/misc";

export function setDates(taskId: string, begin?: string, end?: string): Promise<Issue>
{
    return fetch(ApiUrl.TASK.replace('{taskId}', taskId), {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(cleanObject({
            begin: begin,
            end: end,
        })),
    }).then(res => res.json());
}
