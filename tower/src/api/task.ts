import {ApiUrl} from "@/constants/api";
import {Issue} from "@/types/Issue";
import {cleanObject} from "@/utils/misc";

export function setDates(taskId: string, estimatedBeginDate?: string, estimatedEndDate?: string): Promise<Issue>
{
    return fetch(ApiUrl.TASK.replace('{taskId}', taskId), {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(cleanObject({
            estimatedBeginDate: estimatedBeginDate,
            estimatedEndDate: estimatedEndDate,
        })),
    }).then(res => res.json());
}
