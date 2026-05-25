import { FeatureGate, User, Account } from './models';

export interface PageProps {
    auth: {
        user: User | null;
        account: Account | null;
    };
    featureGates: Record<string, FeatureGate>;
    flash: {
        success: string | null;
        error: string | null;
    };
}
