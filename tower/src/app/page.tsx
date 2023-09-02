'use client'

import useSWR from 'swr'

import {default as TaskMap} from "@/components/Map/Map";
import Card from "@/components/Card/Card";
import React, {useState} from "react";
import Slot from "@/components/Slot/Slot";
import {getDates, shiftDate} from "@/utils/date";
import Marker, {MarkerPosition} from "@/components/Marker/Marker";
import Link from "@/components/Link/Link";
import Task from "@/components/Task/Task";
import Console from "@/components/Console/Console";
import {ApiUrl} from "@/constants/api";
import {Issue} from "@/types/Issue";
import {LinkDescription} from "@/types/LinkDescription";

export default function Page()
{
    const [scale, setScale] = useState<number>(1);
    const handleScaleKeyDown = (event: React.KeyboardEvent<HTMLDivElement>) => {
        if (['=', '+'].includes(event.key)) {
            setScale(scale + 0.1);
        } else if (['-', '_'].includes(event.key)) {
            setScale(scale - 0.1);
        }
    };

    const now = new Date();
    const initialDates = getDates(shiftDate(now, -15), shiftDate(now, 15));
    const [dates, setDates] = useState<string[]>(initialDates);

    const [sizeTaskId, setSizeTaskId] = useState<string|null>(null);
    const onSize = (taskId: string) => {
        setSizeTaskId(taskId);
    }

    const [schedule, setSchedule] = useState<Issue[]>([]);

    const {data, mutate} = useSWR(ApiUrl.TASKS, (api: string) => fetch(api).then(res => res.json()));

    const onMutate = async (fetcher: Function, mutation: {taskId: string, begin?: string, end?: string}, force: boolean = false) => {
        const correction = schedule.find(current => current.key === mutation.taskId);
        if (correction && !force) {
            correction.estimatedBeginDate = mutation.begin ?? correction.estimatedBeginDate;
            correction.estimatedEndDate = mutation.end ?? correction.estimatedEndDate;
            setSchedule(schedule);
        } else {
            const optimisticData = data.map((issue: Issue) =>
                issue.key === mutation.taskId ?
                    {
                        ...issue,
                        estimatedBeginDate: mutation.begin ?? issue.estimatedBeginDate,
                        estimatedEndDate: mutation.end ?? issue.estimatedEndDate,
                    } :
                    issue);
            await mutate(fetcher,{
                optimisticData: optimisticData,
                populateCache: false,
            });
        }
    }
    const onLink = (fetcher: Function) => {
        mutate(fetcher,{
            populateCache: false,
        });
    }

    const links = Array.from<[string, LinkDescription]>(data ? data.reduce((acc: Map<string, LinkDescription>, issue: Issue) => {
        Object.entries(issue.links).reduce((acc, [type, links]) => {
            links.reduce((acc, link) => {
                const key = type === 'outward' ? `${issue.key}-${link.key}` : `${link.key}-${issue.key}`;
                acc.set(key, {
                    start: type === 'outward' ? issue.key : link.key,
                    finish: type === 'outward' ? link.key : issue.key,
                });
                return acc;
            }, acc);
            return acc;
        }, acc);
        return acc;
    }, new Map<string, LinkDescription>()) : new Map<string, LinkDescription>())
        .map(([key, link]: [string, LinkDescription]) => (
            <Link
                key={key}
                startMarkerId={link.start}
                finishMarkerId={link.finish}
            />
        ));

    const scheduledIssues = data ? data.map((issue: Issue) => {
        const correction = schedule.find(current => current.key === issue.key);
        return {
            ...issue,
            ...(correction ? correction : {}),
            corrected: !!correction,
        }
    }) : [];

    const tasks = scheduledIssues.map((issue: Issue) =>
        <Task
            key={issue.key}
            id={issue.key}
            markerLeft={
                <Marker
                    id={issue.key}
                    position={MarkerPosition.Left}
                    onSize={onSize}
                />
            }
            markerRight={
                <Marker
                    id={issue.key}
                    position={MarkerPosition.Right}
                    onSize={onSize}
                />
            }
            begin={issue.estimatedBeginDate}
            end={issue.estimatedEndDate}
            card={
                <Card
                    key={issue.key}
                    id={issue.key}
                    title={issue.summary}
                    corrected={issue.corrected}
                />
            }
            onLink={onLink}
        />
    );

    const slots = sizeTaskId !== null ? dates
        .map(date =>
            <Slot
                key={date}
                id={sizeTaskId}
                position={date}
                onMutate={onMutate}
            />
        ) : [];

    return (
        <>
            <div tabIndex={0} onKeyDown={handleScaleKeyDown}>
                <TaskMap
                    scale={scale}
                    dates={dates}
                    tasks={tasks}
                    slots={slots}
                    links={links}
                />
            </div>
            <div>
                <Console
                    context={{
                        issues: scheduledIssues,
                        schedule: schedule,
                    }}
                    setters={{
                        setScale: setScale,
                        setDates: setDates,
                        setSchedule: setSchedule,
                        onMutate: onMutate,
                    }}
                />
            </div>
        </>
    );
}
