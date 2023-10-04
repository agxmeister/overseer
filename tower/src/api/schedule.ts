import {Schedule} from "@/types/Schedule";
import {ApiUrl} from "@/constants/api";
import {format} from "@/utils/date";

export function getSchedule(date: Date): Promise<Schedule>
{
    return fetch(ApiUrl.SCHEDULE, {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            date: format(date),
        }),
    })
        .then(res => res.json())
}
