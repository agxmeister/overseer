import {getDateArg} from "@/console/utils";
import {format} from "@/utils/date";
import {ApiUrl} from "@/constants/api";

export default async function task(args: string[], onMutate: Function): Promise<string[]>
{
    const lines = [];
    try {
        const taskId = getTaskIdArg(args);
        const beginDate = getBeginDateArg(args);
        const endDate = getEndDateArg(args);
        await onMutate(() => {
            return fetch(ApiUrl.TASK.replace('{taskId}', taskId), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    estimatedBeginDate: format(beginDate),
                    estimatedEndDate: format(endDate),
                }),
            }).then(res => res.json())
        }, {taskId: taskId, begin: format(beginDate), end: format(endDate)}, true);
    } catch (err) {
        lines.unshift(`${err}`);
    }
    return lines;
}

function getTaskIdArg(args: string[]): string
{
    if (!args[1]) {
        throw `Task ID is not specified.`;
    }
    return args[1];
}

function getBeginDateArg(args: string[]): Date
{
    const startDateDeclaration = args.find(arg => arg.startsWith('begin='));
    if (!startDateDeclaration) {
        throw `Begin date is not specified.`;
    }
    return getDateArg(startDateDeclaration.slice(6));
}

function getEndDateArg(args: string[]): Date
{
    const startDateDeclaration = args.find(arg => arg.startsWith('end='));
    if (!startDateDeclaration) {
        throw `End date is not specified.`;
    }
    return getDateArg(startDateDeclaration.slice(4));
}
